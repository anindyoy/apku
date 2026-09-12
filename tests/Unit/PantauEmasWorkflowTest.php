<?php

use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

test('pantau_emas_membersihkan_hanya_run_sukses_lama', function () {
    $workflow = Yaml::parseFile(__DIR__.'/../../.github/workflows/pantau-harga-emas.yml');
    $job = $workflow['jobs']['bersihkan-riwayat'];
    $step = $job['steps'][0];

    expect($job['needs'])->toBe('pantau-endpoint')
        ->and($job['permissions'])->toBe(['actions' => 'write'])
        ->and($job['if'] ?? 'success()')->toBe('success()')
        ->and($step['continue-on-error'])->toBeTrue();

    $script = $step['with']['script'];
    $harness = <<<'JS'
    const assert = require('node:assert/strict');
    const context = {repo: {owner: 'pemilik', repo: 'apku'}, runId: 200};
    const core = {info() {}};
    const deleted = [];
    const run = (id, overrides = {}) => ({id, workflow_id: 9, status: 'completed', conclusion: 'success', ...overrides});
    const github = {
      rest: {actions: {
        getWorkflowRun: async (params) => {
          assert.deepEqual(params, {...context.repo, run_id: 200});
          return {data: {workflow_id: 9}};
        },
        listWorkflowRuns: () => {},
        deleteWorkflowRun: async (params) => { deleted.push(params.run_id); },
      }},
      paginate: async (method, params) => {
        assert.equal(method, github.rest.actions.listWorkflowRuns);
        assert.deepEqual(params, {...context.repo, workflow_id: 9, status: 'success', per_page: 100});
        return [run(201), run(200), run(199), run(198, {conclusion: 'failure'}),
          run(197, {status: 'in_progress', conclusion: null}), run(196, {workflow_id: 10}),
          run(195, {conclusion: 'cancelled'}), ...Array.from({length: 110}, (_, i) => run(i + 1))];
      },
    };
    JS;

    $process = new Process(['node', '-e', $harness."\n(async () => {\n".$script."\nassert.deepEqual(deleted, [199, ...Array.from({length: 110}, (_, i) => i + 1)]);\n})().catch(error => {console.error(error); process.exit(1);});"]);
    $process->run();

    expect($process->getExitCode())->toBe(0, $process->getErrorOutput());
});
