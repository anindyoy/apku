<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="color-scheme" content="light" />
    <meta name="supported-color-schemes" content="light" />
    <title>Invoice Pembayaran Langganan {{ $order->kode_order }} — APKu</title>
    <style type="text/css">
        body, table, td, a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }
        body {
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            background-color: #F8FAFC;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #334155;
        }
        @media only screen and (max-width: 620px) {
            .email-container {
                width: 100% !important;
                max-width: 100% !important;
            }
            .content-cell {
                padding: 24px 20px !important;
            }
            .header-cell {
                padding: 24px 20px 16px 20px !important;
            }
            .cta-button {
                display: block !important;
                width: 100% !important;
                box-sizing: border-box !important;
                text-align: center !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #F8FAFC;">
    {{-- Preheader tersembunyi untuk pratinjau di aplikasi email --}}
    <div style="display: none; font-size: 1px; color: #F8FAFC; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all;">
        Invoice pembayaran langganan {{ $order->kode_order }}. Total tagihan: Rp {{ number_format($order->total_pembayaran, 0, ',', '.') }}.
    </div>

    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #F8FAFC; min-height: 100vh;">
        <tr>
            <td align="center" style="padding: 32px 16px;">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" class="email-container" style="max-width: 600px; margin: 0 auto;">
                    {{-- Kartu Utama --}}
                    <tr>
                        <td style="background-color: #FFFFFF; border-radius: 16px; border: 1px solid #E2E8F0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);">
                            {{-- Garis aksen hijau teal APKu --}}
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td height="5" style="background-color: #0F766E; font-size: 1px; line-height: 1px;">&nbsp;</td>
                                </tr>
                            </table>

                            {{-- Header dengan Logo Aplikasi APKu --}}
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center" class="header-cell" style="padding: 36px 40px 16px 40px;">
                                        <a href="{{ config('app.url') }}" target="_blank" rel="noopener noreferrer" style="text-decoration: none; display: inline-block;">
                                            <img src="{{ asset('logo-options/apku-dompet-wordmark.png') }}"
                                                 alt="{{ config('app.name', 'APKu') }}"
                                                 width="144"
                                                 style="display: block; width: 144px; max-width: 100%; height: auto; border: 0; outline: none; margin: 0 auto;" />
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            {{-- Konten Utama --}}
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td class="content-cell" style="padding: 16px 40px 36px 40px; text-align: left;">
                                        {{-- Badge Status --}}
                                        <div style="text-align: center; margin-bottom: 20px;">
                                            <span style="display: inline-block; background-color: #FEF3C7; color: #92400E; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 6px 14px; border-radius: 9999px;">
                                                Menunggu Pembayaran
                                            </span>
                                        </div>

                                        {{-- Judul Utama --}}
                                        <h1 style="color: #0F172A; font-size: 22px; font-weight: 700; line-height: 1.35; margin: 0 0 16px 0; text-align: center;">
                                            Invoice Pembayaran Langganan
                                        </h1>

                                        {{-- Sapaan --}}
                                        <p style="color: #1E293B; font-size: 15px; line-height: 1.6; margin: 0 0 12px 0;">
                                            Halo, <strong>{{ $user->name ?? 'Pengguna APKu' }}</strong>!
                                        </p>

                                        <p style="color: #475569; font-size: 15px; line-height: 1.65; margin: 0 0 20px 0;">
                                            Terima kasih telah memesan paket langganan di <strong>APKu</strong>. Berikut adalah rincian tagihan pembayaran Anda:
                                        </p>

                                        {{-- Informasi Order --}}
                                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; margin: 0 0 20px 0; font-size: 14px;">
                                            <tr>
                                                <td style="padding: 14px 18px; border-bottom: 1px solid #E2E8F0; color: #64748B;">Kode Order</td>
                                                <td align="right" style="padding: 14px 18px; border-bottom: 1px solid #E2E8F0; color: #0F172A; font-weight: 700;">{{ $order->kode_order }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 14px 18px; border-bottom: 1px solid #E2E8F0; color: #64748B;">Tanggal Order</td>
                                                <td align="right" style="padding: 14px 18px; border-bottom: 1px solid #E2E8F0; color: #0F172A;">{{ $order->created_at?->format('d M Y H:i') ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 14px 18px; border-bottom: 1px solid #E2E8F0; color: #64748B;">Paket Langganan</td>
                                                <td align="right" style="padding: 14px 18px; border-bottom: 1px solid #E2E8F0; color: #0F172A; font-weight: 600;">{{ $order->label_paket }} ({{ $order->durasi_hari }} hari)</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 14px 18px; border-bottom: 1px solid #E2E8F0; color: #64748B;">Harga Paket</td>
                                                <td align="right" style="padding: 14px 18px; border-bottom: 1px solid #E2E8F0; color: #0F172A;">Rp {{ number_format($order->harga, 0, ',', '.') }}</td>
                                            </tr>
                                            @if (filled($order->kode_voucher))
                                            <tr>
                                                <td style="padding: 14px 18px; border-bottom: 1px solid #E2E8F0; color: #64748B;">Diskon Voucher ({{ $order->kode_voucher }} · {{ $order->persentase_diskon }}%)</td>
                                                <td align="right" style="padding: 14px 18px; border-bottom: 1px solid #E2E8F0; color: #047857; font-weight: 600;">− Rp {{ number_format($order->nominal_diskon, 0, ',', '.') }}</td>
                                            </tr>
                                            @endif
                                            <tr style="background-color: #FEF3C7;">
                                                <td style="padding: 14px 18px; color: #92400E; font-weight: 700; border-bottom-left-radius: 12px;">Total Tagihan</td>
                                                <td align="right" style="padding: 14px 18px; color: #92400E; font-weight: 800; font-size: 16px; border-bottom-right-radius: 12px;">Rp {{ number_format($order->total_pembayaran, 0, ',', '.') }}</td>
                                            </tr>
                                        </table>

                                        {{-- Informasi Rekening Tujuan Pembayaran --}}
                                        @php($detail = $order->detail_pembayaran ?? [])
                                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #F0FDFA; border: 1px solid #CCFBF1; border-radius: 12px; margin: 0 0 24px 0;">
                                            <tr>
                                                <td style="padding: 16px 20px;">
                                                    <p style="margin: 0 0 6px 0; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #0F766E;">
                                                        Tujuan Pembayaran
                                                    </p>
                                                    <p style="margin: 0 0 4px 0; font-size: 16px; font-weight: 700; color: #0F172A;">
                                                        {{ $order->label_metode_pembayaran }}
                                                        @if (filled(data_get($detail, 'nama_penyedia')) && data_get($detail, 'nama_penyedia') !== $order->label_metode_pembayaran)
                                                            <span style="font-size: 14px; font-weight: normal; color: #475569;">({{ data_get($detail, 'nama_penyedia') }})</span>
                                                        @endif
                                                    </p>
                                                    @if (filled(data_get($detail, 'nomor_tujuan')))
                                                    <div style="background-color: #FFFFFF; border: 1px solid #99F6E4; border-radius: 8px; padding: 12px 16px; margin: 10px 0;">
                                                        <span style="display: block; font-size: 11px; text-transform: uppercase; color: #0F766E; font-weight: 600;">Nomor Tujuan / Rekening</span>
                                                        <span style="display: block; font-size: 20px; font-weight: 800; color: #0F172A; letter-spacing: 0.05em;">{{ data_get($detail, 'nomor_tujuan') }}</span>
                                                    </div>
                                                    @endif
                                                    @if (filled(data_get($detail, 'nama_pemilik')))
                                                    <p style="margin: 0 0 4px 0; font-size: 13px; color: #334155;">
                                                        Atas Nama: <strong>{{ data_get($detail, 'nama_pemilik') }}</strong>
                                                    </p>
                                                    @endif
                                                    @if (filled(data_get($detail, 'instruksi')))
                                                    <p style="margin: 8px 0 0 0; font-size: 13px; line-height: 1.5; color: #475569;">
                                                        {{ data_get($detail, 'instruksi') }}
                                                    </p>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>

                                        {{-- Langkah Lanjutan --}}
                                        <p style="color: #475569; font-size: 14px; line-height: 1.6; margin: 0 0 20px 0;">
                                            Setelah melakukan pembayaran, silakan unggah bukti transfer di halaman Langganan APKu Anda untuk diverifikasi oleh admin.
                                        </p>

                                        {{-- Tombol Aksi --}}
                                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 20px 0 24px 0;">
                                            <tr>
                                                <td align="center">
                                                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td align="center" style="border-radius: 10px; background-color: #0F766E;">
                                                                <a href="{{ $url }}"
                                                                   target="_blank"
                                                                   rel="noopener noreferrer"
                                                                   class="cta-button"
                                                                   style="display: inline-block; padding: 14px 32px; font-size: 15px; font-weight: 600; color: #FFFFFF; text-decoration: none; border-radius: 10px; background-color: #0F766E; border: 1px solid #0F766E; letter-spacing: 0.01em;">
                                                                    Kirim Bukti Pembayaran
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        {{-- Catatan Penting --}}
                                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; margin: 16px 0 0 0;">
                                            <tr>
                                                <td style="padding: 14px 18px; font-size: 13px; line-height: 1.55; color: #64748B;">
                                                    <strong style="color: #334155;">💡 Tips:</strong> Pastikan nominal yang Anda transfer tepat sampai digit terakhir agar konfirmasi dapat diproses lebih cepat. Apabila email notifikasi tidak masuk ke kotak masuk utama, silakan periksa folder Spam atau Promosi.
                                                </td>
                                            </tr>
                                        </table>

                                        {{-- Tautan Cadangan --}}
                                        <div style="margin-top: 24px; padding-top: 16px; border-top: 1px dashed #E2E8F0;">
                                            <p style="margin: 0 0 6px 0; font-size: 12px; color: #94A3B8; line-height: 1.5;">
                                                Jika tombol di atas tidak dapat diklik, salin dan tempel tautan di bawah ke peramban (browser) Anda:
                                            </p>
                                            <p style="margin: 0; font-size: 12px; line-height: 1.5; word-break: break-all;">
                                                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" style="color: #0F766E; text-decoration: underline;">
                                                    {{ $url }}
                                                </a>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer Email --}}
                    <tr>
                        <td align="center" style="padding: 24px 16px 8px 16px; text-align: center;">
                            <p style="margin: 0 0 4px 0; font-size: 13px; font-weight: 600; color: #475569;">
                                APKu — Catat uang, pahami keuangan
                            </p>
                            <p style="margin: 0 0 12px 0; font-size: 12px; color: #94A3B8;">
                                Aplikasi pencatatan keuangan pribadi dan kolaborasi kas bersama.
                            </p>
                            <p style="margin: 0 0 16px 0; font-size: 12px; color: #64748B;">
                                <a href="{{ config('app.url') }}" target="_blank" rel="noopener noreferrer" style="color: #0F766E; text-decoration: none; font-weight: 500;">Beranda</a>
                                &nbsp;•&nbsp;
                                <a href="{{ route('tutorial') }}" target="_blank" rel="noopener noreferrer" style="color: #0F766E; text-decoration: none; font-weight: 500;">Tutorial Penggunaan</a>
                            </p>
                            <p style="margin: 0; font-size: 11px; color: #94A3B8;">
                                &copy; {{ date('Y') }} APKu. Seluruh hak cipta dilindungi.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
