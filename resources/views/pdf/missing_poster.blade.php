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
            width: 190mm;
            min-height: 277mm;
            margin: 0 auto;
            padding: 10px;
            box-sizing: border-box;
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
            font-size: 44px;
            font-weight: 900;
            color: #b12a1a;
            margin: 38px 0 25px 0;
            letter-spacing: 2px;
        }

        .info-card {
            background: #fffaf6;
            border: 1.7px solid #e6d8d8;
            padding: 18px 28px 18px 28px;
            box-sizing: border-box;
            margin: 0 auto 0 auto;
            width: 90%;
            text-align: center;
            display: flex;
            gap: 32px;
            align-items: center;
            /* 上下居中 */
            justify-content: center;
            /* 左右居中 */
        }

        .photo-box {
            width: 175px;
            height: 200px;
            background: #eee;
            border: 2px solid #bbb;
            display: flex;
            /* flex模式 */
            align-items: center;
            /* 垂直居中 */
            justify-content: center;
            /* 水平居中 */
            overflow: hidden;
            margin: 0 auto;
            /* 再保证父容器时，盒子本身也居中 */
        }

        .photo-box img {
            max-width: 165px;
            max-height: 190px;
            object-fit: cover;
        }

        .main-info-details {
            font-size: 19px;
            color: #222;
            padding-top: 10px;
        }

        .main-info-details b {
            font-weight: 700;
        }

        .last-seen-row {
            margin: 0 auto 14px auto;
            width: 97%;
            text-align: left;
        }

        .last-seen-row span {
            font-weight: 700;
            color: #b12a1a;
            font-size: 18px;
        }

        .desc-table {
            width: 100%;
            margin: 0 auto 10px auto;
            border-collapse: collapse;
        }

        .desc-table td {
            font-size: 16px;
            padding: 0 30px 8px 0;
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
            margin: 24px 0 14px 0;
        }

        .contact-title {
            font-size: 21px;
            font-weight: bold;
            color: #b12a1a;
            letter-spacing: 2px;
            text-align: center;
            margin-bottom: 6px;
        }

        .contact-info {
            font-size: 18px;
            text-align: center;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .logo-row {
            text-align: center;
            margin-top: 2px;
        }

        .logo-row img {
            height: 25px;
            vertical-align: middle;
            margin: 0 7px;
        }

        .platform-link {
            color: #b12a1a;
            font-weight: bold;
            font-size: 15px;
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
            margin-top: 8px;
            font-size: 15px;
            color: #444;
            text-align: center;
            font-weight: bold;
            margin-bottom: 5px;
        }

        /* Ensure the poster is always horizontally centered even when printed */
        @page {
            margin: 0;
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
        <div class="info-card">
            <div class="photo-box">
                @if (!empty($photo_paths) && count($photo_paths) > 0)
                    <img src="{{ public_path('storage/' . $photo_paths[0]) }}" alt="Photo">
                @else
                    <span style="font-size:32px;color:#bbb;">No Photo</span>
                @endif
            </div>
            <div class="main-info-details">
                <div><b>Name:</b> {{ $report->full_name }}</div>
                <div><b>Gender:</b> {{ $report->gender }}</div>
                <div><b>Age:</b> {{ $report->age }}</div>
                <div><b>Height:</b> {{ $report->height_cm }} cm</div>
                <div><b>Weight:</b> {{ $report->weight_kg }} kg</div>
            </div>
        </div>
        <div class="last-seen-row">
            <span>Last Seen Location:</span>{{ $report->last_seen_location ?? '-' }}<br>
            <span>Last Seen
                Date:</span>{{ $report->last_seen_date ? \Carbon\Carbon::parse($report->last_seen_date)->format('j F Y') : '-' }}
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