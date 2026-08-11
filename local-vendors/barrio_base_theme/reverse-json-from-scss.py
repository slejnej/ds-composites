#!/usr/bin/env python3
"""
Reverse engineer SCSS variables back to Figma tokens JSON format
Usage: python3 scss_to_json.py --root-dir /path/to/scss/files
"""

import re
import json
import os
import sys
import argparse
from pathlib import Path
from typing import Dict, Any, List, Optional

class ScssToFigmaJson:
    def __init__(self, root_dir: str = '.'):
        self.root_dir = root_dir
        self.result = {}
        self.current_section = None
        self.current_group = {}
        self.current_group_name = None
        self.current_nested = []
        self.in_comment = False
        self.current_comment = ""

        # Map SCSS sections to JSON sections
        self.section_map = {
            'text': 'text',
            'surface': 'surface',
            'borders': 'borders',
            'components': 'components',
            'layout': 'layout',
            'forms': 'forms'
        }

        # Map SCSS files to output JSON files
        # All files are in the same root directory
        self.file_map = {
            '_components.scss': 'components.tokens.json',
            '_alt-1.scss': 'alt-1.tokens.json',
            '_alt-2.scss': 'alt-2.tokens.json',
            '_global.scss': 'primitives.json',
            '_text-desktop.scss': 'text-desktop.tokens.json',
            '_text-mobile.scss': 'text-mobile.tokens.json'
        }

    def parse_scss_file(self, scss_content: str, palette_name: str) -> Dict:
        """Parse SCSS content and extract variables for a single palette"""
        lines = scss_content.split('\n')

        result = {}
        current_group = {}
        current_group_name = None
        current_section = None
        sections = ['text', 'surface', 'borders', 'layout', 'forms', 'components']

        for line in lines:
            line = line.strip()

            if not line or line.startswith('@import'):
                continue

            # Check for top-level section comments (e.g., "// text")
            if line.startswith('//') and not line.startswith('// '):
                clean = line.replace('//', '').strip().lower()
                if clean in sections:
                    current_section = clean
                    # Store previous group
                    if current_group:
                        self._store_group(result, current_section, current_group_name, current_group)
                        current_group = {}
                        current_group_name = None
                    continue

            # Check for group headers (e.g., "// btn-primary")
            if line.startswith('// '):
                # Store previous group
                if current_group:
                    self._store_group(result, current_section, current_group_name, current_group)
                    current_group = {}

                # Extract group name
                current_group_name = line.replace('// ', '').strip()
                continue

            # Parse variable declaration
            if line.startswith('$'):
                match = re.match(r'^\$([^:]+):\s*(.+);$', line)
                if match:
                    var_name = match.group(1)
                    var_value = match.group(2)

                    # Determine if this is a group or section-level variable
                    if current_group_name:
                        # Store in current group
                        current_group[var_name] = var_value
                    elif current_section:
                        # Store directly in section
                        if current_section not in result:
                            result[current_section] = {}
                        result[current_section][var_name] = self._create_token(var_value)

        # Store the last group
        if current_group:
            self._store_group(result, current_section, current_group_name, current_group)

        return result

    def _store_group(self, result: Dict, section: Optional[str], group_name: Optional[str], group: Dict):
        """Store a group of variables in the result structure"""
        if not group:
            return

        if not section:
            return

        if section not in result:
            result[section] = {}

        target = result[section]

        if group_name:
            # Handle nested groups (e.g., "image-left" in featured-article)
            parts = group_name.split()
            current = target
            for part in parts:
                clean_part = re.sub(r'[^a-zA-Z0-9_-]', '_', part)
                if clean_part not in current:
                    current[clean_part] = {}
                current = current[clean_part]

            # Add variables to the group
            for var_name, var_value in group.items():
                clean_var = self._extract_var_name(var_name, parts)
                current[clean_var] = self._create_token(var_value)
        else:
            # Add to section directly
            for var_name, var_value in group.items():
                target[var_name] = self._create_token(var_value)

    def _extract_var_name(self, full_name: str, group_parts: List[str]) -> str:
        """Extract variable name without the group prefix"""
        clean = full_name
        for part in group_parts:
            prefix = part.lower().replace(' ', '-')
            if clean.startswith(prefix):
                clean = clean[len(prefix):]
                if clean.startswith('-'):
                    clean = clean[1:]
                break
        return clean

    def _create_token(self, value: str) -> Dict:
        """Create a Figma token from a SCSS value"""
        # Determine type
        if re.match(r'^[\d.]+(px|rem|em|%)?$', value):
            token_type = 'number'
        elif value.startswith('$') or '#' in value or 'rgba' in value or 'rgb' in value:
            token_type = 'color'
        elif value in ['fit-content', 'auto', 'unset', 'center', 'left', 'right']:
            token_type = 'string'
        else:
            token_type = 'string'

        return {
            '$type': token_type,
            '$value': value
        }

    def convert_primitives(self, data: Dict) -> Dict:
        """Convert primitives data to the correct structure"""
        result = {}

        # Map primitives sections
        for key, value in data.items():
            if key in ['global-background', 'global-text', 'global-spacing', 'global-color', 'global-border', 'images', 'button', 'banner-section']:
                result[key] = value
            elif key == 'left sidebar':
                result['left sidebar'] = value
            elif key == 'right sidebar':
                result['right sidebar'] = value
            elif key == 'breadcrumb':
                result['breadcrumb'] = value
            elif key == 'hero-caption':
                result['hero-caption'] = value
            elif key == 'carousel':
                result['carousel'] = value
            elif key == 'content':
                result['content'] = value
            elif key == 'feature article':
                result['feature article'] = value
            elif key == 'feature pod':
                result['feature pod'] = value
            elif key == 'footer':
                result['footer'] = value
            elif key == 'intro':
                result['intro'] = value
            elif key == 'pod':
                result['pod'] = value
            elif key == 'promo':
                result['promo'] = value
            elif key == 'section':
                result['section'] = value
            elif key == 'navbar':
                result['navbar'] = value

        return result

    def process_all_files(self):
        """Process all SCSS files in the root directory"""
        results = {}
        processed_count = 0
        skipped_files = []

        for scss_file, json_file in self.file_map.items():
            input_path = os.path.join(self.root_dir, scss_file)

            if not os.path.exists(input_path):
                skipped_files.append(scss_file)
                continue

            print(f"📖 Processing {scss_file}...")

            with open(input_path, 'r') as f:
                scss_content = f.read()

            # Determine palette name from file
            palette_name = scss_file.replace('_', '').replace('.scss', '')

            # Parse the SCSS file
            parsed_data = self.parse_scss_file(scss_content, palette_name)

            # Special handling for primitives
            if palette_name == 'primitives':
                parsed_data = self.convert_primitives(parsed_data)

            # Save to JSON
            output_path = os.path.join(self.root_dir, json_file)
            with open(output_path, 'w') as f:
                json.dump(parsed_data, f, indent=2)

            print(f"✅ Generated {json_file}")
            processed_count += 1
            # Remove the len() call - _count_vars already returns an int
            results[palette_name] = self._count_vars(parsed_data)

        return results, processed_count, skipped_files

    def _count_vars(self, data: Dict) -> int:
        """Count the number of variables in a structure"""
        count = 0
        if isinstance(data, dict):
            for value in data.values():
                if isinstance(value, dict):
                    if '$type' in value:
                        count += 1
                    else:
                        count += self._count_vars(value)
        return count

    def print_stats(self, results: Dict, processed_count: int, skipped_files: List[str]):
        """Print statistics about the conversion"""
        print("\n📊 Conversion Statistics:")
        total_vars = 0
        for palette, count in results.items():
            total_vars += count
            print(f"  {palette}: {count} variables")
        print(f"  Total: {total_vars} variables across {processed_count} files")

        if skipped_files:
            print(f"\n⚠️ Skipped files (not found):")
            for file in skipped_files:
                print(f"  - {file}")

def main():
    parser = argparse.ArgumentParser(
        description='Convert SCSS variables to Figma tokens JSON format',
        epilog='Example: python3 scss_to_json.py --root-dir /path/to/scss/files'
    )
    parser.add_argument(
        '--root-dir',
        '-r',
        default='.',
        help='Root directory containing SCSS files (default: current directory)'
    )
    parser.add_argument(
        '--quiet',
        '-q',
        action='store_true',
        help='Suppress verbose output'
    )

    args = parser.parse_args()

    try:
        # Check if root directory exists
        if not os.path.exists(args.root_dir):
            print(f"❌ Error: Root directory not found: {args.root_dir}")
            sys.exit(1)

        if not args.quiet:
            print(f"📁 Root directory: {os.path.abspath(args.root_dir)}")
            print(f"📂 Looking for SCSS files...")

        # Process all files
        converter = ScssToFigmaJson(args.root_dir)
        results, processed_count, skipped_files = converter.process_all_files()

        # Print statistics
        if not args.quiet:
            converter.print_stats(results, processed_count, skipped_files)
            print(f"\n✨ Conversion complete!")
            print(f"📂 All JSON files saved to: {os.path.abspath(args.root_dir)}")

        if processed_count == 0:
            print(f"\n⚠️ Warning: No SCSS files were processed!")
            print(f"Expected files: {', '.join(converter.file_map.keys())}")
            sys.exit(1)

    except Exception as e:
        print(f"❌ Error: {str(e)}")
        if not args.quiet:
            import traceback
            traceback.print_exc()
        sys.exit(1)

if __name__ == "__main__":
    main()