#!/usr/bin/env python3
"""
SCSS Unused Variable Finder
"""

import os
import re
import sys
import shutil
import datetime
from pathlib import Path
from collections import defaultdict
from typing import Dict, List, Set, Optional, Union
import argparse

args = None

class SCSSVariableAnalyzer:
    def __init__(self, project_folder: str, main_files: List[str], remove_mode: bool = False):
        self.project_folder = Path(project_folder).resolve()
        self.main_files: List[Path] = []

        # Parse main files
        for main_file in main_files:
            path = Path(main_file)
            if not path.is_absolute():
                path = self.project_folder / path
            if path.exists():
                self.main_files.append(path)
            else:
                print(f"Warning: Main file not found: {main_file}")

        if not self.main_files:
            print("Error: No valid main files specified!")
            sys.exit(1)

        self.remove_mode = remove_mode

        # Exclude patterns (files we'll analyze but not remove from)
        self.exclude_patterns = [
            'contrib/bootstrap5',
            'node_modules',
            'vendor',
            'bower_components'
        ]

        # Data storage
        self.all_scss_files: Set[Path] = set()
        self.variable_declarations: Dict[str, List[Dict]] = defaultdict(list)
        self.variable_usages: Dict[str, Set[tuple]] = defaultdict(set)  # (file_path, line)

        # For removal (only files not in exclude patterns)
        self.unused_variables_by_file: Dict[Path, List[Dict]] = defaultdict(list)

        # Stats
        self.stats = {
            'total_files': 0,
            'total_variables': 0,
            'variables_removed': 0,
            'files_modified': 0,
            'backups_created': 0,
            'main_files_processed': 0,
            'files_excluded': 0,
            'variables_excluded': 0
        }

    # ============================================
    # Helper methods
    # ============================================

    def _get_relative_path(self, file_path: Path) -> str:
        """Get relative path from project folder, or full path if not in project."""
        try:
            return str(file_path.relative_to(self.project_folder))
        except ValueError:
            # File is not in project folder (e.g., contrib theme in parent directory)
            return str(file_path)

    def _is_excluded_file(self, file_path: Path) -> bool:
        """Check if a file should be excluded from removal."""
        file_str = str(file_path)
        for pattern in self.exclude_patterns:
            if pattern in file_str:
                return True
        return False

    def _get_file_category(self, file_path: Path) -> str:
        """Get file category for reporting."""
        file_str = str(file_path)
        if 'contrib/bootstrap5' in file_str:
            return 'bootstrap5'
        elif 'contrib/barrio' in file_str:
            return 'barrio'
        elif any(pattern in file_str for pattern in ['node_modules', 'vendor', 'bower_components']):
            return 'external'
        else:
            return 'custom'

    # ============================================
    # STEP 1: Create list of all files to import
    # ============================================

    def _resolve_import(self, import_path: str, current_file: Path) -> Optional[Path]:
        """Resolve an import path relative to current file."""
        import_path = import_path.strip()

        # Skip external imports and js modules
        if any(pattern in import_path for pattern in ['node_modules', '~', 'http://', 'https://']):
            return None

        base_dir = current_file.parent

        # Build list of paths to try in order of appearance
        paths_to_try = []

        # 1 Exact path
        paths_to_try.append(base_dir / import_path)

        # 2 With .scss extension (if not already present)
        if not (import_path.endswith('.scss') or import_path.endswith('.css')):
            paths_to_try.append(base_dir / f"{import_path}.scss")

        # 3. Try underscore version (SCSS partial)
        # If import has directory (all underscores are like that) add underscore to filename part
        if '/' in import_path:
            parts = import_path.rsplit('/', 1)
            dir_part = parts[0]
            file_part = parts[1]
            if not file_part.startswith('_'):
                # Try with underscore and .scss
                if not (import_path.endswith('.scss') or import_path.endswith('.css')):
                    paths_to_try.append(base_dir / dir_part / f"_{file_part}.scss")
        else:
            # No directory in import
            if not import_path.startswith('_') and not (import_path.endswith('.scss') or import_path.endswith('.css')):
                paths_to_try.append(base_dir / f"_{import_path}.scss")

        # Try all variations
        for path in paths_to_try:
            try:
                resolved = path.resolve()
                if resolved.exists() and resolved.is_file():
                    if 'barrio' in import_path and 'style' in import_path:
                        print(f"    FOUND: {resolved}")
                    return resolved
            except Exception:
                continue

        return None

    def _get_all_scss_files(self) -> None:
        """Get all SCSS files by following imports from ALL main files."""
        global args
        print("[Step 1/3] Finding all SCSS files...")

        import_pattern = re.compile(r'@(?:import|use|forward)\s+[\'"]([^\'"]+)[\'"]\s*;?')

        # Start with all main files
        to_process = self.main_files.copy()
        processed = set()

        while to_process:
            file_path = to_process.pop()

            if file_path in processed or not file_path.exists():
                continue

            processed.add(file_path)
            self.all_scss_files.add(file_path)

            try:
                content = file_path.read_text(encoding='utf-8', errors='ignore')

                # Find all imports in this file
                for match in import_pattern.finditer(content):
                    import_path = match.group(1)
                    resolved = self._resolve_import(import_path, file_path)

                    if resolved and resolved not in processed:
                        to_process.append(resolved)

            except Exception as e:
                print(f"  Error reading {file_path}: {e}")

        self.stats['total_files'] = len(self.all_scss_files)
        self.stats['main_files_processed'] = len(self.main_files)
        print(f"  Found {self.stats['total_files']} SCSS files")
        print(f"  Processed {self.stats['main_files_processed']} main files:")
        for main_file in self.main_files:
            rel_path = self._get_relative_path(main_file)
            print(f"    - {rel_path}")

        # Debug: Write all files to a log
        if args.debug:
            debug_log_path = self.project_folder / 'unused-scss-variables_files-found.txt'
            with open(debug_log_path, 'w', encoding='utf-8') as f:
                f.write("All SCSS files found:\n")
                f.write("=" * 80 + "\n\n")
                for file_path in sorted(self.all_scss_files):
                    rel_path = self._get_relative_path(file_path)
                    f.write(f"{rel_path}\n")

            print(f"  File list written to: {debug_log_path}")

    # ================================================
    # STEP 2: Get all variable definitions from files
    # ================================================

    def _clean_content(self, content: str) -> str:
        """Remove comments from content."""
        # Remove block comments
        content = re.sub(r'/\*.*?\*/', '', content, flags=re.DOTALL)
        # Remove line comments
        content = re.sub(r'//.*$', '', content, flags=re.MULTILINE)
        return content

    def _extract_variable_declarations(self) -> None:
        """Extract all variable declarations from all files."""
        print("[Step 2/3] Extracting variable declarations...")

        # Pattern to match variable declarations (including !default)
        # Fixed: Use word boundary to avoid matching $$variable
        decl_pattern = re.compile(r'\$([\w-]+)\s*:\s*([^;]+(?:!default)?)\s*;')

        for file_path in self.all_scss_files:
            try:
                content = file_path.read_text(encoding='utf-8', errors='ignore')
                rel_path = self._get_relative_path(file_path)
            except Exception as e:
                print(f"  Error reading {file_path}: {e}")
                continue

            # Clean content (remove comments)
            cleaned = self._clean_content(content)
            lines = cleaned.split('\n')
            original_lines = content.split('\n')

            for line_num, line in enumerate(lines, 1):
                # Find variable declarations in current line
                for match in decl_pattern.finditer(line):
                    var_name = match.group(1)  # Just the name without $
                    value = match.group(2)
                    context = original_lines[line_num-1].strip() if line_num-1 < len(original_lines) else ""

                    self.variable_declarations[var_name].append({
                        'file': rel_path,
                        'line': line_num,
                        'context': context,
                        'file_path': file_path,
                        'value': value.strip(),
                        'category': self._get_file_category(file_path)
                    })

        self.stats['total_variables'] = len(self.variable_declarations)
        print(f"  Found {self.stats['total_variables']} variable declarations")

    # ============================================
    # STEP 3: Find usages in all files
    # ============================================

    def _find_variable_usages(self) -> None:
        """Find all variable usages in all files."""
        print("[Step 3/3] Finding variable usages...")

        # Get all declared variable names for checking
        all_variable_names = set(self.variable_declarations.keys())

        for file_path in self.all_scss_files:
            try:
                content = file_path.read_text(encoding='utf-8', errors='ignore')
                rel_path = self._get_relative_path(file_path)
            except Exception as e:
                print(f"  Error reading {file_path}: {e}")
                continue

            lines = content.split('\n')

            for line_num, line in enumerate(lines, 1):
                # Find all interpolation blocks and extract variables from them
                interpol_pattern = re.compile(r'#\{([^}]+)\}')

                # Find all interpolation blocks
                for interpol_match in interpol_pattern.finditer(line):
                    interpol_content = interpol_match.group(1)

                    # Extract variables from interpolation content
                    var_matches_in_interpol = re.finditer(r'\$([\w-]+)', interpol_content)
                    for var_match in var_matches_in_interpol:
                        var_name = var_match.group(1)  # Just the name without $
                        if var_name in all_variable_names:
                            self.variable_usages[var_name].add((file_path, line_num))

                # Remove interpolation blocks from the line to avoid double-counting
                line_without_interpol = interpol_pattern.sub('INTERPOL_PLACEHOLDER', line)

                # Find regular variable usages (not in interpolation)
                var_matches = re.finditer(r'\$([\w-]+)', line_without_interpol)
                for match in var_matches:
                    var_name = match.group(1)  # Just the name without $

                    # Only track if this is a declared variable
                    if var_name not in all_variable_names:
                        continue

                    # Check if this line contains a declaration of this variable
                    is_declaration = False
                    for decl in self.variable_declarations[var_name]:
                        if decl['file_path'] == file_path and decl['line'] == line_num:
                            is_declaration = True
                            break

                    # If it's not a declaration, it's a usage
                    if not is_declaration:
                        self.variable_usages[var_name].add((file_path, line_num))

        print(f"  Usage detection complete")

    # ============================================
    # Analysis and reporting
    # ============================================

    def _analyze_results(self) -> List[Dict]:
        """Analyze to find unused variables."""
        global args
        print("\n[Analysis] Identifying unused variables...")

        unused_variables = []

        for var_name, declarations in self.variable_declarations.items():
            usages = self.variable_usages.get(var_name, set())

            # Create set of declaration locations
            decl_locations = {(decl['file_path'], decl['line']) for decl in declarations}

            # Remove declaration locations from usages
            actual_usages = usages - decl_locations

            # If no actual usages, variable is potentially unused
            if not actual_usages:
                for decl in declarations:
                    # Only mark for removal if file is not excluded
                    if not self._is_excluded_file(decl['file_path']):
                        unused_variables.append({
                            'variable': var_name,
                            'declaration': decl,
                            'usage_count': 0,
                            'raw_usage_count': len(usages),
                            'category': decl['category']
                        })

                        # Group by file for removal
                        file_path = decl['file_path']
                        self.unused_variables_by_file[file_path].append({
                            'variable': var_name,
                            'declaration': decl,
                            'usage_count': 0,
                            'raw_usage_count': len(usages),
                            'category': decl['category']
                        })
                    else:
                        self.stats['variables_excluded'] += 1

        # Sort by file and line
        unused_variables.sort(key=lambda x: (x['declaration']['file'], x['declaration']['line']))

        # Count excluded files
        excluded_files = set()
        for file_path in self.all_scss_files:
            if self._is_excluded_file(file_path):
                excluded_files.add(file_path)
        self.stats['files_excluded'] = len(excluded_files)

        # Show debug info for specific variables
        print("\n" + "-" * 60)
        print("Debug: Checking specific variables:")
        print("-" * 60)

        test_cases = [
            ('stroke-color-encoded', 'Should be used in interpolation'),
            ('alt-1-accordion-body-padding-y', 'Should be used in interpolation'),
            ('navbar-brand-image-width', 'Should be used in width property'),
            ('navbar-brand-site-title-font-size', 'Should be used in font-size'),
            ('banner-height-mobile', 'Should be used in height'),
        ]

        for var_part, reason in test_cases:
            matching = [v for v in self.variable_declarations.keys() if var_part in v]
            for var_name in matching:
                usages = self.variable_usages.get(var_name, set())
                decl_locations = {(d['file_path'], d['line']) for d in self.variable_declarations[var_name]}
                actual_usages = usages - decl_locations

                status = "USED" if actual_usages else "UNUSED"
                usage_count = len(actual_usages)
                category = self.variable_declarations[var_name][0]['category']

                # Remove extra $ in output
                print(f"  ${var_name:40} - {status:10} ({usage_count} usage(s)) [{category}] - {reason}")

                if usage_count == 0 and usages:
                    print(f"    Warning: Has {len(usages)} references but all appear to be declarations")

        return unused_variables

    def analyze(self) -> Dict:
        """Run the complete analysis."""
        print(f"Analyzing SCSS from {len(self.main_files)} main files")
        print(f"Project folder: {self.project_folder}")
        print(f"Remove mode: {'ENABLED' if self.remove_mode else 'DISABLED'}")
        print(f"Excluding files with: {', '.join(self.exclude_patterns)}")
        print("=" * 60)

        # Step 1: Get all SCSS files from ALL main files
        self._get_all_scss_files()

        if not self.all_scss_files:
            print("No SCSS files found!")
            return {'potentially_unused': [], 'stats': self.stats, 'total_variables': 0, 'total_files': 0}

        # Step 2: Get all variable definitions
        self._extract_variable_declarations()

        # Step 3: Find all usages
        self._find_variable_usages()

        # Analyze results
        unused_variables = self._analyze_results()

        return {
            'potentially_unused': unused_variables,
            'stats': self.stats,
            'total_variables': self.stats['total_variables'],
            'total_files': self.stats['total_files']
        }

    # ============================================
    # Removal functionality (same as before)
    # ============================================

    def _create_backup(self, file_path: Path) -> bool:
        """Create a backup of a file."""
        backup_path = file_path.with_suffix(file_path.suffix + '.backup')
        try:
            shutil.copy2(file_path, backup_path)
            self.stats['backups_created'] += 1
            return True
        except Exception as e:
            print(f"  Error creating backup for {file_path}: {e}")
            return False

    def _remove_unused_variables_from_file(self, file_path: Path, unused_vars: List[Dict]) -> bool:
        """Remove unused variables from a file."""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                lines = f.readlines()

            # Get line numbers to remove (1-based)
            lines_to_remove = {var['declaration']['line'] for var in unused_vars}

            # Remove lines in reverse order
            new_lines = []
            removed_count = 0

            for i, line in enumerate(lines, 1):
                if i not in lines_to_remove:
                    new_lines.append(line)
                else:
                    var_name = next((v['variable'] for v in unused_vars
                                     if v['declaration']['line'] == i), 'unknown')
                    print(f"    Removing line {i}: ${var_name}")
                    removed_count += 1

            # Write modified content back
            with open(file_path, 'w', encoding='utf-8') as f:
                f.writelines(new_lines)

            self.stats['variables_removed'] += removed_count
            if removed_count > 0:
                self.stats['files_modified'] += 1
            return True

        except Exception as e:
            print(f"  Error removing variables from {file_path}: {e}")
            return False

    def remove_unused_variables(self, results: Dict) -> None:
        """Remove unused variables from files (excluding contrib/bootstrap5)."""
        if not self.remove_mode:
            return

        print("\n" + "=" * 80)
        print("REMOVING UNUSED VARIABLES")
        print("=" * 80)
        print(f"NOTE: Skipping files in: {', '.join(self.exclude_patterns)}")

        # Filter out excluded files
        filtered_unused = {}
        for file_path, unused_vars in self.unused_variables_by_file.items():
            if not self._is_excluded_file(file_path):
                filtered_unused[file_path] = unused_vars

        if not filtered_unused:
            print("\nNo unused variables to remove (after excluding protected files).")
            return

        # Ask for confirmation
        total_to_remove = sum(len(vars) for vars in filtered_unused.values())
        print(f"\nAbout to remove {total_to_remove} variable(s) from {len(filtered_unused)} file(s)")

        # Show what will be removed
        print("\nFirst 20 variables to be removed:")
        print("-" * 40)
        count = 0
        for file_path, unused_vars in filtered_unused.items():
            rel_path = self._get_relative_path(file_path)
            for var_info in unused_vars[:20 - count]:
                count += 1
                print(f"{count:3}. ${var_info['variable']:30} {rel_path}:{var_info['declaration']['line']}")
                if count >= 20:
                    break
            if count >= 20:
                break

        if total_to_remove > 20:
            print(f"... and {total_to_remove - 20} more")

        confirm = input("\nType 'YES' to continue, anything else to cancel: ")

        if confirm != 'YES':
            print("Removal cancelled.")
            return

        # Process each file
        for file_path, unused_vars in filtered_unused.items():
            # Sort by line number (descending for safe removal)
            unused_vars.sort(key=lambda x: x['declaration']['line'], reverse=True)

            rel_path = self._get_relative_path(file_path)
            print(f"\n{rel_path}")
            print(f"  Removing {len(unused_vars)} variable(s)")

            # Create backup
            if self._create_backup(file_path):
                # Remove variables
                if self._remove_unused_variables_from_file(file_path, unused_vars):
                    print(f"  Successfully removed {len(unused_vars)} variable(s)")
                else:
                    print(f"  Failed to remove variables")
            else:
                print(f"  Skipping removal due to backup failure")

    def print_results(self, results: Dict) -> None:
        """Print analysis results."""
        print("\n" + "=" * 80)
        print("SCSS VARIABLE ANALYSIS RESULTS")
        print("=" * 80)

        # Print statistics
        print(f"\nStatistics:")
        print(f"  Main files processed: {self.stats['main_files_processed']}")
        print(f"  Total files found: {results['total_files']}")
        print(f"  Files excluded from removal: {self.stats['files_excluded']}")
        print(f"  Variables found: {results['total_variables']}")
        print(f"  Variables excluded from removal: {self.stats['variables_excluded']}")

        if self.remove_mode:
            print(f"  Backups created: {self.stats['backups_created']}")
            print(f"  Files modified: {self.stats['files_modified']}")
            print(f"  Variables removed: {self.stats['variables_removed']}")

        # Print potentially unused variables
        unused_vars = results['potentially_unused']

        if not unused_vars:
            print(f"\nNo potentially unused variables found!")
            return

        print(f"\nFound {len(unused_vars)} potentially unused variables (excluding contrib/bootstrap5)")
        print("-" * 80)

        # Export to file
        timestamp = datetime.datetime.now().strftime("%Y%m%d_%H%M%S")
        export_path = self.project_folder / f'unused-scss-variables_{timestamp}.txt'

        try:
            with open(export_path, 'w', encoding='utf-8') as f:
                f.write("Potentially Unused SCSS Variables\n")
                f.write("=" * 50 + "\n\n")
                f.write(f"Generated from {len(self.main_files)} main files:\n")
                for main_file in self.main_files:
                    rel_path = self._get_relative_path(main_file)
                    f.write(f"  - {rel_path}\n")
                f.write(f"\nProject folder: {self.project_folder}\n")
                f.write(f"Total files processed: {results['total_files']}\n")
                f.write(f"Files excluded from removal: {self.stats['files_excluded']}\n")
                f.write(f"Variables found: {results['total_variables']}\n")
                f.write(f"Variables excluded from removal: {self.stats['variables_excluded']}\n")
                f.write(f"Potentially unused: {len(unused_vars)}\n")
                f.write(f"Generated at: {datetime.datetime.now()}\n\n")
                f.write("EXCLUDED PATTERNS (not removed):\n")
                for pattern in self.exclude_patterns:
                    f.write(f"  - {pattern}\n")
                f.write("\n")

                # Group by file
                by_file = {}
                for item in unused_vars:
                    file = item['declaration']['file']
                    if file not in by_file:
                        by_file[file] = []
                    by_file[file].append(item)

                # Write by file
                for file, items in sorted(by_file.items()):
                    f.write(f"\n{file}\n")
                    f.write("-" * 40 + "\n\n")

                    for item in items:
                        var_name = item['variable']
                        decl = item['declaration']
                        f.write(f"${var_name} (line {decl['line']})\n")
                        f.write(f"  {decl['context']}\n")
                        f.write(f"  Raw references found: {item['raw_usage_count']}\n")
                        f.write(f"  Actual usages: {item['usage_count']}\n\n")

            print(f"\nResults exported to: {export_path}")

            # Show summary in console
            print(f"\nSummary by file:")
            print("-" * 40)

            by_file = {}
            for item in unused_vars:
                file = item['declaration']['file']
                if file not in by_file:
                    by_file[file] = 0
                by_file[file] += 1

            for file, count in sorted(by_file.items())[:20]:
                print(f"{file}: {count} variable(s)")

            if len(by_file) > 20:
                print(f"... and {len(by_file) - 20} more files")

        except Exception as e:
            print(f"\nCould not export results: {e}")


def main():
    global args
    parser = argparse.ArgumentParser(
        description='Find and optionally remove unused SCSS variables from multiple entry points',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  # Single main file
  %(prog)s /path/to/web "themes/custom/tadej_theme/assets/scss/style.scss"
  
  # Multiple main files (comma-separated)
  %(prog)s /path/to/web "themes/custom/tadej_theme/assets/scss/style.scss,themes/custom/tadej_theme/assets/scss/bootstrap.scss"
  
  # Also analyze barrio_base_theme (but don't remove from it)
  %(prog)s /path/to/web "themes/custom/tadej_theme/assets/scss/style.scss,themes/contrib/barrio_base_theme/assets/scss/style.scss" --exclude "contrib/bootstrap5,contrib/barrio"
        """
    )

    parser.add_argument('folder', help='Project folder (usually Drupal web root)')
    parser.add_argument('main_files', help='Main SCSS file(s) to start analysis from (comma-separated)')
    parser.add_argument('--remove', action='store_true', help='Remove unused variables (with backup)')
    parser.add_argument('--debug', action='store_false', default=False,
                        help='Creates two files with full detection list and variables to remove')
    parser.add_argument('--exclude', default='contrib/bootstrap5',
                        help='Comma-separated patterns to exclude from removal (default: contrib/bootstrap5)')

    args = parser.parse_args()

    # Validate inputs
    project_folder = Path(args.folder).resolve()
    if not project_folder.exists():
        print(f"Error: Folder '{project_folder}' does not exist")
        sys.exit(1)

    # Process main files
    main_files_list = []
    for main_file in args.main_files.split(','):
        main_file = main_file.strip()
        if not main_file:
            continue

        path = Path(main_file)
        if not path.is_absolute():
            path = project_folder / path

        if path.exists():
            main_files_list.append(str(path))
        else:
            print(f"Warning: Main file not found: {main_file}")

    if not main_files_list:
        print("Error: No valid main files specified!")
        sys.exit(1)

    # Process exclude patterns
    exclude_patterns = [p.strip() for p in args.exclude.split(',') if p.strip()]

    print(f"\nProcessing {len(main_files_list)} main files:")
    for f in main_files_list:
        print(f"  - {f}")
    print(f"Excluding files with: {', '.join(exclude_patterns)}")

    # Run analysis
    analyzer = SCSSVariableAnalyzer(project_folder, main_files_list, args.remove)

    # Override exclude patterns if specified
    if exclude_patterns:
        analyzer.exclude_patterns = exclude_patterns

    results = analyzer.analyze()

    # Remove variables if requested
    if args.remove:
        analyzer.remove_unused_variables(results)

    analyzer.print_results(results)

    # Final warning
    if args.remove:
        print("\n" + "=" * 80)
        print("IMPORTANT NOTES:")
        print("=" * 80)
        print("1. Backups were created with .backup extension")
        print("2. Files matching exclude patterns were NOT modified")
        print("3. Review changes before committing")
        print("4. Test your application thoroughly")
        print("5. To restore from backup: cp file.scss.backup file.scss")
        print("=" * 80)


if __name__ == '__main__':
    main()
