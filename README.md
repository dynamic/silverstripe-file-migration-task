# SilverStripe File Migration Task

### Summary
This Build Task allows for traversing a directory recursively and migrate those files into the SilverStripe filesystem. The task checks against the allowed extensions on [`File`](https://github.com/silverstripe/silverstripe-assets/blob/1/src/File.php#L165-L185).

## Requirements

* SilverStripe Assets ^1.0

## Installation

`composer require dynamic/silverstripe-file-migration-task`

## Usage

### Configuration

```yaml
Dynamic\FileMigration\Tasks\FileMigrationTask:
  # Path to directory with files (required)
  existing_file_system_path: '/path/to/your/files'
  # Base folder to create in Assets (optional)
  base_upload_folder: '/uploads'
```