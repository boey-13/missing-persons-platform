<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Missing Person Poster</title>
    <style>
        body {
            font-family: 'Poppins', Arial, Helvetica, 'DejaVu Sans', sans-serif;
            background: #fff;
            color: #222;
            margin: 0;
            padding: 0;
        }

        .poster-wrap {
            /* use @page margins to avoid dompdf blank first page */
            width: 100%;
            height: auto;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            page-break-inside: avoid;
            overflow: hidden;
            word-break: break-word;
        }

        .header-row {
            width: 100%;
            margin: 0 0 10px 0;
            position: relative;
        }

        .urgent {
            background: #b12a1a;
            color: #fff;
            font-weight: bold;
            font-size: 18px;
            padding: 4px 18px 4px 18px;
            border-radius: 7px;
            letter-spacing: 1px;
            display: inline-block;
            position: absolute;
            left: 0;
            top: 0;
        }

        .case-no {
            color: #666;
            font-size: 15px;
            font-weight: bold;
            position: absolute;
            right: 0;
            top: 6px;
        }

        .main-title {
            width: 100%;
            text-align: center;
            font-size: 40px;
            font-weight: 900;
            color: #b12a1a;
            margin: 6mm 0 4mm 0;
            letter-spacing: 2px;
        }

        .info-card {
            background: #fff8f5;
            border: 2px solid #e6d8d8;
            padding: 5mm;
            box-sizing: border-box;
            margin: 0 auto 4mm auto;
            width: 100%;
            text-align: center;
            display: flex;
            flex-direction: column;
            gap: 4mm;
            align-items: center;
            justify-content: center;
        }

        .photo-box {
            width: 68mm;
            height: 85mm;
            background: #e5e7eb; /* gray-200 */
            border: 2px solid #9ca3af; /* gray-400 */
            display: flex;
            /* flex模式 */
            align-items: center;
            /* 垂直居中 */
            justify-content: center;
            /* 水平居中 */
            overflow: hidden;
            margin: 0 auto;
            /* 再保证父容器时，盒子本身也居中 */
            border-radius: 8px;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.08);
        }

        .photo-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }

        .main-info-details {
            font-size: 10pt;
            color: #222;
            padding-top: 2mm;
        }

        .main-info-details b {
            font-weight: 700;
        }

        .last-seen-row {
            margin: 0 0 4mm 0;
            width: 100%;
            text-align: left;
            border-left: 4px solid #b12a1a;
            padding-left: 4mm;
        }

        .last-seen-row span {
            font-weight: 700;
            color: #b12a1a;
            font-size: 11pt;
        }

        .last-seen-value {
            font-size: 11pt;
            color: #111;
        }

        .name-large {
            font-size: 14pt;
            font-weight: 800;
        }

        .desc-table {
            width: 100%;
            margin: 0 0 4mm 0;
            border-collapse: collapse;
        }

        .desc-table td {
            font-size: 11pt;
            padding: 0 20px 4px 0;
            vertical-align: top;
        }

        .section-label {
            font-weight: bold;
            color: #b12a1a;
            margin-bottom: 2px;
        }

        .footer-hr {
            border: none;
            border-top: 2px solid #ddd;
            width: 100%;
            margin: 4mm 0 3mm 0;
        }

        .contact-title {
            font-size: 12pt;
            font-weight: 900;
            color: #222;
            letter-spacing: 2px;
            text-align: center;
            margin-bottom: 3mm;
        }

        .contact-info {
            font-size: 11pt;
            text-align: center;
            font-weight: bold;
            margin-bottom: 4mm;
        }

        .logo-row {
            text-align: center;
            margin-top: 2mm;
        }

        .logo-row img {
            height: 18px;
            vertical-align: middle;
            margin: 0 7px;
        }

        .platform-link {
            color: #b12a1a;
            font-weight: bold;
            font-size: 10pt;
            text-align: center;
            display: block;
        }

        .platform-link a {
            color: #1a53b0;
            font-size: 14px;
            font-weight: normal;
            text-decoration: underline;
        }

        .share-reminder {
            margin-top: 3mm;
            font-size: 10pt;
            color: #444;
            text-align: center;
            font-weight: bold;
            margin-bottom: 0;
        }

        /* A4 sizing for dompdf */
        @page {
            size: A4 portrait;
            margin: 10mm; /* set page margins here; wrapper has no outer margin */
        }
    </style>
</head>

<body>
    <div class="poster-wrap">
        <div class="header-row" style="height: 28px;">
            <span class="urgent">URGENT</span>
            <span class="case-no">Case No: {{ $report->id }}</span>
        </div>
        <div class="main-title">MISSING PERSON</div>
        <div class="info-card" style="width:190mm; margin:0 auto;">
            <div class="photo-box">
                @if (!empty($photo_paths) && count($photo_paths) > 0)
                    <img src="{{ public_path('storage/' . $photo_paths[0]) }}" alt="Photo">
                @else
                    <span style="font-size:32px;color:#bbb;">No Photo</span>
                @endif
            </div>
            <div class="main-info-details">
                <div class="name-large">{{ $report->full_name }}</div>
                <div><b>Gender:</b> {{ $report->gender }}</div>
                <div><b>Age:</b> {{ $report->age }}</div>
                <div><b>Height:</b> {{ $report->height_cm }} cm</div>
                <div><b>Weight:</b> {{ $report->weight_kg }} kg</div>
            </div>
        </div>
        <div class="last-seen-row">
            <span>Last Seen Location:</span> <span class="last-seen-value">{{ $report->last_seen_location ?? '-' }}</span><br>
            <span>Last Seen
                Date:</span> <span class="last-seen-value">{{ $report->last_seen_date ? \Carbon\Carbon::parse($report->last_seen_date)->format('j F Y') : '-' }}</span>
        </div>
        <table class="desc-table">
            <tr>
                <td>
                    <div class="section-label">Physical Description:</div>
                    <div>{{ $report->physical_description ?: '—' }}</div>
                </td>
                <td>
                    <div class="section-label">Clothing Description:</div>
                    <div>{{ $report->last_seen_clothing ?: '—' }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="section-label">Other Notes:</div>
                    <div>{{ $report->additional_notes ?: '—' }}</div>
                </td>
            </tr>
        </table>
        <hr class="footer-hr">
        <div class="contact-title">CONTACT INFORMATION</div>
        <div class="contact-info">
            <span style="font-size:1.15em;">&#9742;</span> {{ $report->contact_number ?: 'Not Provided' }}
        </div>
        <div class="logo-row">
            <img src="{{ public_path('images/findme_logo.png') }}" alt="FindMe Logo" />
            <div class="platform-link">FindMe Platform<br>
                <a href="https://findme.com" target="_blank">https://findme.com</a>
            </div>
        </div>
        <div class="share-reminder">
            Please share this poster to social media and help us find <b>{{ $report->full_name }}</b>!
        </div>
    </div>
</body>

</html>