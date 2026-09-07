import re

def markdown_to_html():
    md_file = r"C:\Users\incai\Web_dev_practical\Web_Final project\PROJECT_REPORT_DRAFT.md"
    html_file = r"C:\Users\incai\Web_dev_practical\Web_Final project\PROJECT_REPORT.html"

    with open(md_file, "r", encoding="utf-8") as f:
        content = f.read()

    # Split lines and build HTML
    html_body = []
    lines = content.splitlines()
    in_code_block = False
    code_lang = ""
    code_lines = []
    in_table = False
    table_rows = []

    for line in lines:
        if line.startswith("```"):
            if not in_code_block:
                in_code_block = True
                code_lines = []
            else:
                in_code_block = False
                html_body.append(f"<pre><code>" + "\n".join(code_lines) + "</code></pre>")
            continue

        if in_code_block:
            code_lines.append(line.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;"))
            continue

        # Handle tables
        if line.startswith("|") and line.endswith("|"):
            if not in_table:
                in_table = True
                table_rows = []
            table_rows.append(line)
            continue
        elif in_table:
            in_table = False
            # parse table_rows
            if len(table_rows) >= 2:
                th_cols = [c.strip() for c in table_rows[0].split("|")[1:-1]]
                tbl_html = ["<table class='report-table'><thead><tr>"]
                for c in th_cols:
                    tbl_html.append(f"<th>{c}</th>")
                tbl_html.append("</tr></thead><tbody>")
                for row in table_rows[2:]:
                    cols = [c.strip() for c in row.split("|")[1:-1]]
                    tbl_html.append("<tr>")
                    for c in cols:
                        # Bold formatting inside cell
                        c_formatted = re.sub(r'\*\*(.*?)\*\*', r'<strong>\1</strong>', c)
                        tbl_html.append(f"<td>{c_formatted}</td>")
                    tbl_html.append("</tr>")
                tbl_html.append("</tbody></table>")
                html_body.append("".join(tbl_html))

        # Headings
        if line.startswith("# "):
            html_body.append(f"<h1 class='doc-title'>{line[2:]}</h1>")
        elif line.startswith("## "):
            html_body.append(f"<h2 class='chapter-title'>{line[3:]}</h2>")
        elif line.startswith("### "):
            html_body.append(f"<h3>{line[4:]}</h3>")
        elif line.startswith("---"):
            html_body.append("<hr/>")
        elif line.startswith("- "):
            formatted = re.sub(r'\*\*(.*?)\*\*', r'<strong>\1</strong>', line[2:])
            html_body.append(f"<ul><li>{formatted}</li></ul>")
        elif line.startswith("1. ") or line.startswith("2. ") or line.startswith("3. ") or line.startswith("4. ") or line.startswith("5. "):
            formatted = re.sub(r'\*\*(.*?)\*\*', r'<strong>\1</strong>', line[3:])
            html_body.append(f"<ol><li>{formatted}</li></ol>")
        elif line.strip() == "":
            continue
        else:
            formatted = re.sub(r'\*\*(.*?)\*\*', r'<strong>\1</strong>', line)
            formatted = re.sub(r'`(.*?)`', r'<code>\1</code>', formatted)
            html_body.append(f"<p>{formatted}</p>")

    full_html = f"""<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PharmRx - Final Project Report (IBL12307)</title>
    <style>
        @page {{
            size: A4;
            margin: 20mm 15mm;
        }}
        body {{
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1e293b;
            line-height: 1.6;
            margin: 0 auto;
            max-width: 900px;
            padding: 2rem;
            background: #fff;
        }}
        .doc-title {{
            color: #0f172a;
            font-size: 28px;
            border-bottom: 3px solid #06b6d4;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }}
        .chapter-title {{
            color: #1e3a8a;
            font-size: 22px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 6px;
            margin-top: 35px;
            page-break-before: auto;
        }}
        h3 {{
            color: #0369a1;
            margin-top: 20px;
        }}
        p, li {{
            font-size: 14px;
            color: #334155;
        }}
        pre {{
            background: #0f172a;
            color: #38bdf8;
            padding: 14px;
            border-radius: 6px;
            overflow-x: auto;
            font-family: 'Consolas', monospace;
            font-size: 12px;
            border: 1px solid #334155;
        }}
        code {{
            background: #f1f5f9;
            color: #0284c7;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Consolas', monospace;
            font-size: 13px;
        }}
        pre code {{
            background: transparent;
            color: inherit;
            padding: 0;
        }}
        .report-table {{
            width: 100%;
            border-collapse: collapse;
            margin: 18px 0;
            font-size: 13px;
        }}
        .report-table th, .report-table td {{
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            text-align: left;
        }}
        .report-table th {{
            background: #f8fafc;
            color: #0f172a;
            font-weight: 600;
        }}
        .report-table tr:nth-child(even) {{
            background: #f8fafc;
        }}
        hr {{
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 25px 0;
        }}
        .print-btn {{
            position: fixed;
            top: 20px;
            right: 20px;
            background: #0284c7;
            color: white;
            padding: 10px 18px;
            font-size: 14px;
            font-weight: bold;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }}
        .print-btn:hover {{
            background: #0369a1;
        }}
        @media print {{
            .print-btn {{ display: none; }}
            body {{ padding: 0; max-width: 100%; }}
            .chapter-title {{ page-break-before: always; }}
            .doc-title {{ page-break-before: avoid; }}
        }}
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
    {"\n".join(html_body)}
</body>
</html>"""

    with open(html_file, "w", encoding="utf-8") as f:
        f.write(full_html)
    print("SUCCESS: HTML Report generated at", html_file)

if __name__ == "__main__":
    markdown_to_html()
