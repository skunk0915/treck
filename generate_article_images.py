import os
import glob
import re
import sys
import requests
from openai import OpenAI
from dotenv import load_dotenv

# Try to import google-generativeai
try:
    import google.generativeai as genai
    GOOGLE_LIB_AVAILABLE = True
except ImportError:
    GOOGLE_LIB_AVAILABLE = False

# Load environment variables
load_dotenv()

# Configuration
ARTICLE_DIR = os.getenv("ARTICLE_DIR", "article")
IMG_DIR = os.getenv("IMG_DIR", "img")
CHAR_LIMIT = int(os.getenv("CHAR_LIMIT", "300"))

# Secrets and Models
API_KEY = os.getenv("API_KEY")
BASE_URL = os.getenv("BASE_URL") # Optional
TEXT_MODEL = os.getenv("TEXT_MODEL", "gpt-4o")
IMAGE_MODEL = os.getenv("IMAGE_MODEL", "dall-e-3")

def setup_openai_client():
    if not API_KEY:
        print("Error: API_KEY environment variable not set.")
        print("Please set it in a .env file.")
        sys.exit(1)
    
    return OpenAI(
        api_key=API_KEY,
        base_url=BASE_URL if BASE_URL else None
    )

def setup_google_client():
    if not GOOGLE_LIB_AVAILABLE:
        print("Error: google-generativeai library not installed.")
        print("Please run: pip install google-generativeai")
        sys.exit(1)
    
    if not API_KEY:
        print("Error: API_KEY environment variable not set.")
        sys.exit(1)
        
    genai.configure(api_key=API_KEY)
    return True

def generate_image_prompt_openai(client, context, title):
    """Generates an image prompt using OpenAI API."""
    try:
        response = client.chat.completions.create(
            model=TEXT_MODEL,
            messages=[
                {"role": "system", "content": "You are an assistant that creates detailed image prompts based on article text. The images should be suitable for a hiking/outdoor gear blog. Keep the prompt descriptive but concise. Focus on the visual elements described or implied in the text. Return ONLY the prompt text."},
                {"role": "user", "content": f"Article Title: {title}\n\nContext:\n{context}\n\nCreate an image prompt for this context."}
            ]
        )
        return response.choices[0].message.content.strip()
    except Exception as e:
        print(f"Error generating prompt with OpenAI model {TEXT_MODEL}: {e}")
        return None

def generate_image_prompt_google(context, title):
    """Generates an image prompt using Google Gen AI SDK."""
    try:
        model = genai.GenerativeModel(TEXT_MODEL)
        prompt = f"""
        You are an assistant that creates detailed image prompts based on article text. 
        The images should be suitable for a hiking/outdoor gear blog. 
        Keep the prompt descriptive but concise. 
        Focus on the visual elements described or implied in the text. 
        Return ONLY the prompt text.

        Article Title: {title}

        Context:
        {context}

        Create an image prompt for this context.
        """
        response = model.generate_content(prompt)
        return response.text.strip()
    except Exception as e:
        print(f"Error generating prompt with Google model {TEXT_MODEL}: {e}")
        return None

def generate_and_save_image_openai(client, prompt, output_path):
    """Generates an image using OpenAI API and saves it."""
    try:
        response = client.images.generate(
            model=IMAGE_MODEL,
            prompt=prompt,
            size="1792x1024", # 16:9 aspect ratio (approx)
            quality="standard",
            n=1,
            style="natural"
        )
        image_url = response.data[0].url
        
        # Download and save
        img_data = requests.get(image_url).content
        with open(output_path, 'wb') as handler:
            handler.write(img_data)
        
        return True
    except Exception as e:
        print(f"Error generating image with OpenAI model {IMAGE_MODEL}: {e}")
        return False

def generate_and_save_image_google(prompt, output_path):
    """Generates an image using Google Gen AI SDK and saves it."""
    try:
        # Use GenerativeModel for nano-banana-pro-preview
        model = genai.GenerativeModel(IMAGE_MODEL)
        
        # For image generation, we just pass the prompt. 
        # The model should return an image in the response parts.
        response = model.generate_content(prompt)
        
        if response.parts:
            for part in response.parts:
                if hasattr(part, 'inline_data') and part.inline_data and part.inline_data.mime_type.startswith('image/'):
                    with open(output_path, 'wb') as f:
                        f.write(part.inline_data.data)
                    return True
                # Fallback for some versions where inline_data might be accessed differently
                # But usually part.inline_data.data is correct for Blob
        
        print("Google model returned no images.")
        return False
            
    except Exception as e:
        print(f"Error generating image with Google model {IMAGE_MODEL}: {e}")
        return False

def process_file(openai_client, filepath, is_google_text, is_google_image):
    filename = os.path.basename(filepath)
    file_root, _ = os.path.splitext(filename)
    
    print(f"Checking {filename}...")
    
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Check for existing images
    existing_images = re.findall(r'!\[.*?\]\(.*?\)', content)
    if existing_images:
        print(f"  - Found {len(existing_images)} existing images in {filename}.")
        while True:
            choice = input(f"  - Overwrite images in {filename}? (y/n): ").lower()
            if choice == 'y':
                content = re.sub(r'!\[.*?\]\(.*?\)\n?', '', content)
                break
            elif choice == 'n':
                print(f"  - Skipping {filename}.")
                return
            else:
                print("Please enter 'y' or 'n'.")

    lines = content.split('\n')
    new_lines = []
    buffer_text = ""
    char_count_since_last_image = 0
    image_index = 1
    
    for i, line in enumerate(lines):
        new_lines.append(line)
        buffer_text += line + "\n"
        char_count_since_last_image += len(line)
        
        is_paragraph_break = (line.strip() == "")
        
        if is_paragraph_break and char_count_since_last_image >= CHAR_LIMIT:
            print(f"  - Generating image {image_index} for {filename}...")
            
            context = buffer_text[-1000:] 
            
            # Generate Prompt
            prompt = None
            if is_google_text:
                prompt = generate_image_prompt_google(context, file_root)
            else:
                prompt = generate_image_prompt_openai(openai_client, context, file_root)
            
            if prompt:
                img_filename = f"{file_root}_{image_index:02d}.png"
                img_path = os.path.join(IMG_DIR, img_filename)
                
                # Generate Image
                success = False
                if is_google_image:
                    success = generate_and_save_image_google(prompt, img_path)
                else:
                    success = generate_and_save_image_openai(openai_client, prompt, img_path)
                
                if success:
                    alt_text = prompt[:80].replace('"', '').replace('\n', ' ') + "..."
                    image_md = f"\n![{alt_text}](../{IMG_DIR}/{img_filename})\n"
                    new_lines.append(image_md)
                    
                    image_index += 1
                    char_count_since_last_image = 0
            else:
                print("    - Failed to generate prompt.")

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write("\n".join(new_lines))
    print(f"  - Finished {filename}. Generated {image_index-1} images.")

def main():
    global TEXT_MODEL, IMAGE_MODEL # Allow modifying global if needed
    
    if not os.path.exists(IMG_DIR):
        os.makedirs(IMG_DIR)
    
    # Heuristics to determine if we are using Google models
    is_google_image = any(x in IMAGE_MODEL.lower() for x in ['google', 'banana', 'gemini', 'imagen'])
    is_google_text = any(x in TEXT_MODEL.lower() for x in ['gemini', 'palm'])
    
    # Auto-switch text model if image model is Google but text model is still default OpenAI
    if is_google_image and TEXT_MODEL == "gpt-4o":
        print("Google Image Model detected. Switching default Text Model to 'nano-banana-pro-preview'.")
        TEXT_MODEL = "nano-banana-pro-preview"
        is_google_text = True
    
    # Normalize image model name if it's the banana one
    if "banana" in IMAGE_MODEL.lower():
        IMAGE_MODEL = "nano-banana-pro-preview"

    print(f"Using Text Model: {TEXT_MODEL} ({'Google' if is_google_text else 'OpenAI'})")
    print(f"Using Image Model: {IMAGE_MODEL} ({'Google' if is_google_image else 'OpenAI'})")

    openai_client = None
    if is_google_text or is_google_image:
        setup_google_client()
    else:
        openai_client = setup_openai_client()

    files = sorted(glob.glob(os.path.join(ARTICLE_DIR, "*.md")))
    if not files:
        print("No markdown files found in article directory.")
        return

    print(f"Found {len(files)} articles.")
    
    for filepath in files:
        process_file(openai_client, filepath, is_google_text, is_google_image)

if __name__ == "__main__":
    main()
