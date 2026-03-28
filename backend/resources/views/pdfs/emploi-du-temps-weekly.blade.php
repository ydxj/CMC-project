<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Emploi du temps' }}</title>
    <style>
        @page {
            margin: 18px 18px 20px 18px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 12px;
        }

        .header {
            width: 100%;
            margin-bottom: 8px;
            border-collapse: collapse;
        }

        .header td {
            vertical-align: top;
        }

        .logo-box {
            width: 110px;
        }

        .logo {
            width: 78px;
            height: 78px;
            border: 1px solid #d1d5db;
            border-radius: 50%;
            object-fit: contain;
            padding: 6px;
            background: #ffffff;
        }

        .title {
            font-size: 18px;
            font-weight: 700;
            text-align: center;
            margin-top: 8px;
        }

        .meta {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse;
        }

        .meta td {
            vertical-align: top;
        }

        .meta-left {
            width: 65%;
            font-size: 13px;
            line-height: 1.4;
        }

        .meta-right {
            width: 35%;
            text-align: right;
            font-size: 13px;
            line-height: 1.4;
            font-weight: 600;
        }

        table.planner {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .planner th,
        .planner td {
            border: 1px solid #374151;
        }

        .planner thead th {
            background: #f8fafc;
            font-size: 11px;
            font-weight: 700;
            text-align: center;
            padding: 6px 2px;
        }

        .day-col {
            width: 120px;
            text-align: left;
            padding-left: 8px;
            font-size: 16px;
            font-weight: 700;
            background: #f9fafb;
        }

        .slot-cell {
            height: 52px;
            text-align: center;
            vertical-align: middle;
            background: #ffffff;
        }

        .slot-empty {
            background: #ffffff;
        }

        .slot-session {
            background: #fdfdfd;
            padding: 2px 4px;
        }

        .line1 {
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .line2 {
            font-size: 10px;
            font-style: italic;
        }
    </style>
</head>
<body>
<table class="header">
    <tr>
        <td class="logo-box">
            @if(!empty($logoDataUri))
                <img src="{{ $logoDataUri }}" class="logo" alt="OFPPT" />
            @endif
        </td>
        <td>
            <div class="title">{{ $title ?? 'Emploi du temps' }}</div>
        </td>
    </tr>
</table>

<table class="meta">
    <tr>
        <td class="meta-left">
            @if(!empty($metaLeft))
                @foreach($metaLeft as $line)
                    <div>{{ $line }}</div>
                @endforeach
            @endif
        </td>
        <td class="meta-right">
            @if(!empty($metaRight))
                @foreach($metaRight as $line)
                    <div>{{ $line }}</div>
                @endforeach
            @endif
        </td>
    </tr>
</table>

<table class="planner">
    <thead>
    <tr>
        <th class="day-col">Jour \ Horaire</th>
        @foreach(($slotLabels ?? []) as $label)
            <th>{{ $label }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach(($rows ?? []) as $row)
        <tr>
            <td class="day-col">{{ $row['day'] }}</td>
            @foreach(($row['cells'] ?? []) as $cell)
                @if(($cell['type'] ?? 'empty') === 'session')
                    <td class="slot-cell slot-session" colspan="{{ $cell['colspan'] ?? 1 }}">
                        <div class="line1">{{ $cell['line1'] ?? '-' }}</div>
                        <div class="line2">{{ $cell['line2'] ?? '' }}</div>
                    </td>
                @else
                    <td class="slot-cell slot-empty" colspan="{{ $cell['colspan'] ?? 1 }}">&nbsp;</td>
                @endif
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
