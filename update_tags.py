import os
import re

article_dir = '/Users/mizy/Dropbox/treck/article'

# Tag Mapping Rules
# Key: New Tag, Value: List of keywords to match in filename or content
tag_mapping = {
    'Backpack': ['backpack', 'rucksack', 'daypack', 'saas_fee', 'ridge', 'aircontact', 'gregory', 'osprey', 'mystery_ranch', 'karrimor', 'deuter', 'mammut_backpack', 'black_diamond_backpack', 'north_face_backpack', 'montbell_backpack', 'hmg', 'gossamer', 'palante', 'yamatomichi'],
    'Shoes': ['shoes', 'boots', 'footwear', 'caravan', 'sirio', 'lowa', 'scarpa', 'la_sportiva', 'mammut_shoes', 'montbell_shoes', 'keen', 'merrell', 'salomon', 'hoka', 'altra', 'zamberlan', 'winter_shoes', 'approach'],
    'Tent': ['tent', 'shelter', 'zelt', 'stellaridge', 'arai', 'air_raiz', 'nemo_tani', 'big_agnes', 'msr_tent', 'six_moon', 'zerogram', 'finetrack_zelt'],
    'Sleeping Bag': ['sleeping_bag', 'schlafsack', 'montbell_sleeping', 'nanga', 'isuka', 'western_mountaineering', 'tnf_down'],
    'Mat': ['mat', 'mattress', 'pad', 'thermarest', 'nemo_mat', 'nemo_mattress'],
    'Rainwear': ['rainwear', 'rain', 'poncho', 'montbell_rainwear', 'teton_bros'],
    'Base Layer': ['baselayer', 'underwear', 'tights', 'cwx', 'finetrack_drylayer', 'millet_drynamic', 'icebreaker', 'smartwool', 'merino', 'mountain_shirt'],
    'Insulation': ['insulation', 'down', 'fleece', 'patagonia_r1', 'tnf_down'],
    'Softshell/Windshell': ['softshell', 'windshell', 'mammut_softshell', 'wind_shell'],
    'Bottoms': ['pants', 'shorts', 'skirt', 'trekking_pants', 'boxer_pants'],
    'Trekking Poles': ['trekking_poles', 'stock', 'leki', 'black_diamond_trekking'],
    'Stove & Cookware': ['stove', 'burner', 'cookware', 'jetboil', 'primus', 'epigas', 'soto', 'msr_pocketrocket', 'cutlery', 'water_bottle', 'aluminum_vs_titanium'],
    'Food': ['food', 'snacks', 'freezedried', 'trail_snacks'],
    'Headlamp': ['headlamp', 'light', 'petzl', 'black_diamond_headlamp'],
    'Electronics': ['electronics', 'camera', 'gps', 'garmin', 'mobile_battery'],
    'Accessories': ['gloves', 'hat', 'cap', 'socks', 'gaiter', 'spats', 'arm_cover', 'neck_gaiter', 'sunglasses', 'sunscreen', 'superfeet', 'knee_support'],
    'Safety': ['safety', 'first_aid', 'insurance', 'cocoheli', 'bear_bell', 'helmet'],
    'Maintenance': ['maintenance', 'repair', 'wash'],
    'Packing': ['packing', 'stuff_sack', 'organization'],
    'Guide': ['guide', 'comparison', 'layering', 'climbing_permit']
}

def get_tags_for_file(filename):
    filename_base = filename.replace('.md', '').lower()
    tags = []
    
    for tag, keywords in tag_mapping.items():
        for keyword in keywords:
            if keyword in filename_base:
                tags.append(tag)
                break
    
    # Default to 'Guide' if no specific tag found but it's a guide
    if not tags and 'guide' in filename_base:
        tags.append('Guide')
        
    # Deduplicate
    return list(set(tags))

def update_file(filepath, tags):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Check if Tags already exist
    if re.search(r'^Tags:', content, re.MULTILINE):
        print(f"Skipping {os.path.basename(filepath)}: Tags already exist.")
        return

    # Insert Tags after the title (first line starting with #)
    lines = content.split('\n')
    new_lines = []
    inserted = False
    
    for line in lines:
        new_lines.append(line)
        if not inserted and line.startswith('# '):
            new_lines.append(f"Tags: {', '.join(tags)}")
            inserted = True
            
    if not inserted:
        # If no title found, insert at top
        new_lines.insert(0, f"Tags: {', '.join(tags)}")

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write('\n'.join(new_lines))
    print(f"Updated {os.path.basename(filepath)} with tags: {tags}")

# Process all files
for filename in os.listdir(article_dir):
    if filename.endswith('.md'):
        filepath = os.path.join(article_dir, filename)
        tags = get_tags_for_file(filename)
        if tags:
            update_file(filepath, tags)
        else:
            print(f"No tags matched for {filename}")
