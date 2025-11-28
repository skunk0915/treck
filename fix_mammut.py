import re
import os

file_path = r'c:\Users\OW\pj\blog\article\mammut_backpack_article.md'

with open(file_path, 'r', encoding='utf-8') as f:
    lines = f.readlines()

new_lines = []
for line in lines:
    # Replace full-width colon with half-width colon and space for JK and Sensei
    # Pattern: **JK**： -> **JK**: 
    # Pattern: **先生**： -> **先生**: 
    
    if line.strip().startswith('**JK**：'):
        line = line.replace('**JK**：', '**JK**: ', 1)
    elif line.strip().startswith('**先生**：'):
        line = line.replace('**先生**：', '**先生**: ', 1)
        
    new_lines.append(line)

with open(file_path, 'w', encoding='utf-8') as f:
    f.writelines(new_lines)

print("Fixed mammut_backpack_article.md")
