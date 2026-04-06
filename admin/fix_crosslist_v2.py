import os
import re

filepath = r"d:\htdocs\weknow_projects\well-known\admin\crosslist_functions.php"

with open(filepath, 'r') as f:
    lines = f.readlines()

new_lines = []
is_bed_added = False
num_logic_inserted = False

for i, line in enumerate(lines):
    # Add numbering logic before grouping
    if "if ($t !== $current_type) {" in line and not num_logic_inserted:
        indent = re.match(r"\s*", line).group()
        # Insert numbering logic
        new_lines.append(f"{indent}if (strtolower($t) === 'major') {{\n")
        new_lines.append(f"{indent}    $major_paper_counter++;\n")
        new_lines.append(f"{indent}    $numeral = $romanMap[$major_paper_counter] ?? $major_paper_counter;\n")
        new_lines.append(f"{indent}    $stu['papers'][$idx]['paper_numeral'] = $numeral;\n")
        new_lines.append(f"{indent}    if ($is_bed) {{ $t = 'PAPER ' . $numeral; }}\n")
        new_lines.append(f"{indent}}}\n")
        new_lines.append(line)
        num_logic_inserted = True
        continue
    
    # Remove old numbering logic
    if 'if (strtolower($t) === "major") {' in line or "if (strtolower($t) === 'major') {" in line:
        # Check if next lines are the old logic
        if i + 2 < len(lines) and "$major_paper_counter++;" in lines[i+1] and "paper_numeral" in lines[i+2]:
            # Skip these lines
            continue
    if i > 0 and "$major_paper_counter++;" in line and 'if (strtolower($t) === "major") {' in lines[i-1] or "if (strtolower($t) === 'major') {" in lines[i-1]:
        continue
    if i > 1 and "paper_numeral" in line and 'if (strtolower($t) === "major") {' in lines[i-2] or "if (strtolower($t) === 'major') {" in lines[i-2]:
        continue
    if i > 2 and "}" in line and 'if (strtolower($t) === "major") {' in lines[i-3] or "if (strtolower($t) === 'major') {" in lines[i-3]:
        # This is very fragile. Let's just do a string replacement on the whole block if possible.
        pass

    new_lines.append(line)

content = "".join(lines)

# Robust replacement for the loop part
target_pattern = r'(\s*)if\s*\(\$t\s*!==\s*\$current_type\)\s*\{([\s\S]*?)\}\s*else\s*\{([\s\S]*?)\}\s*if\s*\(strtolower\(\$t\)\s*===\s*\'major\'\)\s*\{([\s\S]*?)\}'

# Actually, I'll just use the literal strings I see in view_file but with flexible whitespace
def fix_loop(c):
    # Find the grouping start
    pattern = re.compile(r'if\s*\(\$t\s*!==\s*\$current_type\)\s*\{')
    match = pattern.search(c)
    if not match: return c, False
    
    start = match.start()
    indent = c[c.rfind('\n', 0, start)+1 : start]
    
    num_logic = f"""if (strtolower($t) === 'major') {{
{indent}    $major_paper_counter++;
{indent}    $numeral = $romanMap[$major_paper_counter] ?? $major_paper_counter;
{indent}    $stu['papers'][$idx]['paper_numeral'] = $numeral;
{indent}    if ($is_bed) {{ $t = 'PAPER ' . $numeral; }}
{indent}}}

{indent}"""
    
    c = c[:start] + num_logic + c[start:]
    
    # Now remove the old one (which is now further down)
    old_logic_pattern = re.compile(r'if\s*\(strtolower\(\$t\)\s*===\s*\'major\'\)\s*\{[\s\S]*?\}', re.MULTILINE)
    # Be careful not to remove the one we just added.
    # Our new one has "if ($is_bed)". The old one doesn't.
    matches = list(old_logic_pattern.finditer(c))
    for m in reversed(matches):
        if "if ($is_bed)" not in m.group():
            c = c[:m.start()] + c[m.end():]
            break
            
    return c, True

new_content, success = fix_loop(content)

if success:
    with open(filepath, 'w') as f:
        f.write(new_content)
    print("Success: Fixed loop via Python")
else:
    print("Error: Could not find loop pattern")
