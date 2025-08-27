<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Missing Person Poster</title>
  <style>
    *{ box-sizing: border-box; }
    body{
      font-family:'Poppins', Arial, Helvetica, 'DejaVu Sans', sans-serif;
      background:#fff; color:#222; margin:0; padding:0;
    }

    /* A4 单页 */
    @page{ size:A4 portrait; margin:10mm; }

    .poster-wrap{ width:100%; page-break-inside:avoid; overflow:visible; word-break:break-word; }
    .page{ width:186mm; margin:0 auto; } /* 居中一个内容宽度 */

    /* 顶部 */
    .header-row{ position:relative; height:28px; margin:0 0 8px 0; }
    .urgent{
      position:absolute; left:0; top:0;
      background:#b12a1a; color:#fff; font-weight:800; font-size:18px;
      padding:4px 18px; border-radius:8px; letter-spacing:1px;
    }
    .case-no{
      position:absolute; right:0; top:4px;
      color:#2a5dab; font-size:15px; font-weight:800;
    }
    .main-title{
      text-align:center; font-size:40px; font-weight:900; color:#b12a1a;
      letter-spacing:2px; margin:8px 0 10px 0;
      border-bottom:1.5px solid #e8e2dc; padding-bottom:6px;
    }

    /* —— 这里开始：没有外框，只做居中栈 —— */
    .hero{
      width:160mm;            /* 主内容列比页面窄，天然居中 */
      margin:0 auto 8mm auto; /* 下方留间距即可 */
      text-align:center;
    }

    /* 照片框（保留原边框 & 底条） */
    .photo-box{
      width:66mm; height:76mm;
      background:#e5e7eb; border:1.5pt solid #b6b9bf; border-radius:8px;
      display:flex; align-items:center; justify-content:center; overflow:hidden; position:relative;
      box-shadow: inset 0 1px 2px rgba(0,0,0,.06);
      margin:0 auto 6mm auto;   /* 居中 + 与姓名拉开距离 */
    }
    .photo-box::after{
      content:""; position:absolute; left:0; right:0; bottom:0; height:5px; background:#d9d9dd;
    }
    .photo-box img{ max-width:100%; max-height:100%; object-fit:cover; display:block; }

    .main-info-details{ font-size:11pt; line-height:1.6; color:#222; }
    .name-large{ font-size:15pt; font-weight:800; margin-bottom:2mm; }
    .main-info-details b{ font-weight:800; }

    /* Last seen */
    .last-seen-row{
      width:160mm; margin:0 auto 6mm auto; /* 与 hero 同宽并居中 */
      border-left:4px solid #b12a1a; padding-left:4mm; text-align:left;
    }
    .last-seen-row .label{ font-weight:900; color:#b12a1a; font-size:11pt; }
    .last-seen-value{ font-size:12pt; font-weight:800; color:#111; }

    /* 描述 */
    .desc-table{
      width:160mm; margin:0 auto 6mm auto; border-collapse:collapse;
    }
    .desc-table td{ width:50%; vertical-align:top; font-size:11pt; padding:0 18px 4px 0; }
    .section-label{ font-weight:800; color:#222; margin-bottom:2px; }

    /* 联系方式 */
    .footer-hr{ border:none; border-top:2px solid #ddd; width:160mm; margin:5mm auto 4mm auto; }
    .contact-title{
      font-size:12pt; font-weight:900; color:#222; letter-spacing:2px; text-align:center; margin-bottom:2.5mm;
    }
    .contact-info{ font-size:11pt; text-align:center; font-weight:700; margin-bottom:4mm; }
    .logo-row{ text-align:center; margin-top:1mm; }
    .logo-row img{ height:35px; vertical-align:middle; margin:0 7px 5px 7px; }
    .platform-link{ color:#b12a1a; font-weight:800; font-size:10pt; text-align:center; display:block; margin-top:1mm; }
    .platform-link a{ color:#1a53b0; font-size:14px; font-weight:normal; text-decoration:underline; }
    .share-reminder{ margin-top:4mm; font-size:10pt; color:#444; text-align:center; font-weight:700; }
  </style>
</head>
<body>
  <div class="poster-wrap">
    <div class="page">
      <div class="header-row">
        <span class="urgent">URGENT</span>
        <span class="case-no">Case No: {{ $report->id }}</span>
      </div>

      <div class="main-title">MISSING PERSON</div>

      <!-- 🔥 没有外框的居中区 -->
      <div class="hero">
                 <div class="photo-box">
           @if (!empty($photo_paths) && count($photo_paths) > 0)
             <img src="{{ public_path('storage/' . $photo_paths[0]) }}" alt="Photo">
           @else
             <img src="{{ base_path('resources/js/assets/default-avatar.jpg') }}" alt="Default Avatar">
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
        <span class="label">Last Seen Location:</span>
        <span class="last-seen-value"> {{ $report->last_seen_location ?? '-' }}</span><br>
        <span class="label">Last Seen Date:</span>
        <span class="last-seen-value">
          {{ $report->last_seen_date ? \Carbon\Carbon::parse($report->last_seen_date)->format('j F Y') : '-' }}
        </span>
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
         <span style="font-size:1.15em;">&#9742;</span>
         011-11223344
       </div>

      <div class="logo-row">
        <img src="{{ base_path('resources/js/assets/brown.png') }}" alt="FindMe Logo" />
        <div class="platform-link">
          FindMe Platform<br>
          <a href="https://findme.com" target="_blank">https://findme.com</a>
        </div>
      </div>

      <div class="share-reminder">
        Please share this poster to social media and help us find <b>{{ $report->full_name }}</b>!
      </div>
    </div>
  </div>
</body>
</html>
