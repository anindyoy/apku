<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="color-scheme" content="light" />
    <meta name="supported-color-schemes" content="light" />
    <title>Konfirmasi Pembayaran Langganan {{ $order->kode_order }} Perlu Diperbaiki — APKu</title>
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
    {{-- Preheader tersembunyi --}}
    <div style="display: none; font-size: 1px; color: #F8FAFC; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all;">
        Konfirmasi bukti pembayaran untuk order {{ $order->kode_order }} perlu diperbaiki. Silakan periksa alasan penolakan dari admin.
    </div>

    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #F8FAFC; min-height: 100vh;">
        <tr>
            <td align="center" style="padding: 32px 16px;">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" class="email-container" style="max-width: 600px; margin: 0 auto;">
                    {{-- Kartu Utama --}}
                    <tr>
                        <td style="background-color: #FFFFFF; border-radius: 16px; border: 1px solid #E2E8F0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);">
                            {{-- Garis aksen merah/amber APKu untuk status penolakan --}}
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td height="5" style="background-color: #DC2626; font-size: 1px; line-height: 1px;">&nbsp;</td>
                                </tr>
                            </table>

                            {{-- Header Logo --}}
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
                                            <span style="display: inline-block; background-color: #FEE2E2; color: #991B1B; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 6px 14px; border-radius: 9999px;">
                                                Perlu Diperbaiki
                                            </span>
                                        </div>

                                        {{-- Judul Utama --}}
                                        <h1 style="color: #0F172A; font-size: 22px; font-weight: 700; line-height: 1.35; margin: 0 0 16px 0; text-align: center;">
                                            Konfirmasi Pembayaran Ditolak
                                        </h1>

                                        {{-- Sapaan --}}
                                        <p style="color: #1E293B; font-size: 15px; line-height: 1.6; margin: 0 0 12px 0;">
                                            Halo, <strong>{{ $user->name ?? 'Pengguna APKu' }}</strong>!
                                        </p>

                                        <p style="color: #475569; font-size: 15px; line-height: 1.65; margin: 0 0 20px 0;">
                                            Mohon maaf, bukti pembayaran yang Anda kirimkan untuk order <strong>#{{ $order->kode_order }}</strong> belum dapat kami setujui.
                                        </p>

                                        {{-- Kotak Alasan Penolakan --}}
                                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #FEF2F2; border: 1px solid #FECACA; border-radius: 12px; margin: 0 0 24px 0;">
                                            <tr>
                                                <td style="padding: 16px 20px;">
                                                    <p style="margin: 0 0 6px 0; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #991B1B;">
                                                        Alasan Penolakan dari Admin
                                                    </p>
                                                    <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #7F1D1D; font-weight: 500;">
                                                        {{ $order->catatan_admin ?: 'Bukti pembayaran tidak sesuai atau tidak terbaca.' }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>

                                        {{-- Ringkasan Order --}}
                                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; margin: 0 0 24px 0; font-size: 14px;">
                                            <tr>
                                                <td style="padding: 14px 18px; border-bottom: 1px solid #E2E8F0; color: #64748B;">Kode Order</td>
                                                <td align="right" style="padding: 14px 18px; border-bottom: 1px solid #E2E8F0; color: #0F172A; font-weight: 700;">{{ $order->kode_order }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 14px 18px; border-bottom: 1px solid #E2E8F0; color: #64748B;">Paket Langganan</td>
                                                <td align="right" style="padding: 14px 18px; border-bottom: 1px solid #E2E8F0; color: #0F172A;">{{ $order->label_paket }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 14px 18px; color: #64748B;">Total Tagihan</td>
                                                <td align="right" style="padding: 14px 18px; color: #0F172A; font-weight: 700;">Rp {{ number_format($order->total_pembayaran, 0, ',', '.') }}</td>
                                            </tr>
                                        </table>

                                        {{-- Langkah Selanjutnya --}}
                                        <p style="color: #334155; font-size: 14px; font-weight: 600; margin: 0 0 8px 0;">
                                            Langkah Lanjutan:
                                        </p>
                                        <p style="color: #475569; font-size: 14px; line-height: 1.65; margin: 0 0 24px 0;">
                                            Anda dapat mengunggah ulang bukti pembayaran yang benar melalui halaman Langganan, atau membuat pesanan paket baru bila diperlukan.
                                        </p>

                                        {{-- Tombol Aksi --}}
                                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 20px 0 20px 0;">
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
                                                                    Buka Halaman Langganan
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        @if (filled($urlWa))
                                        {{-- Bantuan WhatsApp Admin --}}
                                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 0 0 24px 0;">
                                            <tr>
                                                <td align="center">
                                                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td align="center" style="border-radius: 10px; background-color: #25D366;">
                                                                <a href="{{ $urlWa }}"
                                                                   target="_blank"
                                                                   rel="noopener noreferrer"
                                                                   class="cta-button"
                                                                   style="display: inline-block; padding: 12px 28px; font-size: 14px; font-weight: 600; color: #FFFFFF; text-decoration: none; border-radius: 10px; background-color: #25D366; border: 1px solid #25D366;">
                                                                    Hubungi WhatsApp Admin
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                        @endif

                                        {{-- Tautan Cadangan --}}
                                        <div style="margin-top: 24px; padding-top: 16px; border-top: 1px dashed #E2E8F0;">
                                            <p style="margin: 0 0 6px 0; font-size: 12px; color: #94A3B8; line-height: 1.5;">
                                                Jika tombol tidak dapat diklik, salin tautan berikut ke peramban Anda:
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

                    {{-- Footer --}}
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
