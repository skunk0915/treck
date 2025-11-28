import os
import re
from collections import Counter

article_dir = '/Users/mizy/Dropbox/treck/article'
tags_counter = Counter()

for filename in os.listdir(article_dir):
    if filename.endswith('.md'):
        filepath = os.path.join(article_dir, filename)
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
            # Extract filename base
            filename_base = filename.replace('.md', '')
            parts = filename_base.split('_')
            # Filter out common words based on index.php logic
            tags = [t for t in parts if t not in ['guide', 'article', 'review', 'comparison']]
            tags_counter.update(tags)

print("Total unique tags:", len(tags_counter))
print("\nTag Counts:")
for tag, count in tags_counter.most_common():
    print(f"{tag}: {count}")
