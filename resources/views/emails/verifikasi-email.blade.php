<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="color-scheme" content="light" />
    <meta name="supported-color-schemes" content="light" />
    <title>Verifikasi Alamat Email Anda — APKu</title>
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
                padding: 28px 20px !important;
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
        Konfirmasi pendaftaran akun APKu Anda. Klik tombol verifikasi untuk mulai mencatat keuangan dan sumber dana Anda.
    </div>

    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #F8FAFC; min-height: 100vh;">
        <tr>
            <td align="center" style="padding: 32px 16px;">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" class="email-container" style="max-width: 580px; margin: 0 auto;">
                    {{-- Kartu Utama --}}
                    <tr>
                        <td style="background-color: #FFFFFF; border-radius: 16px; border: 1px solid #E2E8F0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);">
                            {{-- Garis aksen hijau teal APKu di bagian atas --}}
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
                                        {{-- Badge Verifikasi --}}
                                        <div style="text-align: center; margin-bottom: 20px;">
                                            <span style="display: inline-block; background-color: #CCFBF1; color: #0F766E; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 6px 14px; border-radius: 9999px;">
                                                Verifikasi Akun Baru
                                            </span>
                                        </div>

                                        {{-- Judul Utama --}}
                                        <h1 style="color: #0F172A; font-size: 22px; font-weight: 700; line-height: 1.35; margin: 0 0 16px 0; text-align: center;">
                                            Konfirmasi Alamat Email Anda
                                        </h1>

                                        {{-- Sapaan dan Paragraf Pengantar --}}
                                        <p style="color: #1E293B; font-size: 15px; line-height: 1.6; margin: 0 0 12px 0;">
                                            Halo, <strong>{{ $nama ?? 'Pengguna APKu' }}</strong>!
                                        </p>

                                        <p style="color: #475569; font-size: 15px; line-height: 1.65; margin: 0 0 24px 0;">
                                            Terima kasih telah mendaftar di <strong>APKu</strong>. Sebelum mulai mencatat transaksi, mengelola kas, dan mengatur sumber dana Anda, silakan konfirmasi bahwa ini adalah alamat email Anda yang aktif.
                                        </p>

                                        {{-- Tombol Aksi Verifikasi --}}
                                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 28px 0 28px 0;">
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
                                                                    Verifikasi Alamat Email
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        {{-- Kotak Catatan Keamanan / Masa Berlaku --}}
                                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; margin: 24px 0 0 0;">
                                            <tr>
                                                <td style="padding: 14px 18px; font-size: 13px; line-height: 1.55; color: #64748B;">
                                                    <strong style="color: #334155;">⏱️ Catatan Keamanan:</strong><br />
                                                    Tautan verifikasi di atas berlaku selama <strong>60 menit</strong>. Jika Anda tidak merasa mendaftar akun di APKu, Anda dapat mengabaikan email ini dengan aman tanpa tindakan apa pun.
                                                </td>
                                            </tr>
                                        </table>

                                        {{-- Tautan Cadangan (Fallback URL) --}}
                                        <div style="margin-top: 28px; padding-top: 20px; border-top: 1px dashed #E2E8F0;">
                                            <p style="margin: 0 0 8px 0; font-size: 12px; color: #94A3B8; line-height: 1.5;">
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