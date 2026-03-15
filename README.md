# squatch-post-exporter
A simple WordPress plugin that takes posts and puts them in a CSV.

## Overview

**Squatch Post Exporter** adds a tool inside the WordPress admin that allows you to export any public post type into a clean CSV file.

It is designed to make **content migration, audits, and backups** easier by exporting:

- Core post data
- Author information
- Featured image URLs
- Taxonomies (names and slugs)
- All post meta fields

The exported file is saved to your WordPress uploads directory and can be downloaded immediately after export.

---

## Features

- Export **any public post type**
- Includes **published, draft, private, and other statuses**
- Automatically includes **all taxonomy terms**
- Automatically discovers and exports **all post meta keys**
- Includes **featured image URLs**
- Generates **clean CSV output**
- Provides **file size and row count after export**
- Accessible directly from **Tools → Squatch Post Exporter**

---

## Installation

### Manual Installation

1. Download or clone this repository.
2. Upload the plugin folder to:

```
/wp-content/plugins/
```

3. Activate the plugin in **WordPress Admin → Plugins**.

---

## Usage

1. Go to:

```
Tools → Squatch Post Exporter
```

2. Select the **post type** you want to export.

3. Click **Export Posts**.

4. The plugin will:

- Generate the CSV file
- Save it to:
- 
```
/wp-content/uploads/squatch-exports/
```

5. A **download link** will appear when the export completes.

---

## CSV Output

The CSV file includes the following base columns:
```
| Column | Description |
|------|------|
| Post ID | WordPress post ID |
| Title | Post title |
| Slug | Post slug |
| Permalink | Full URL to the post |
| Post Status | Published, Draft, etc |
| Author Username | Author login |
| Author Email | Author email |
| Publish Date | Post publish date |
| Content | Full post content |
| Featured Image URL | URL of featured image |
```

### Taxonomies

Each taxonomy adds **two columns**:

- Term Names
- Term Slugs

Example:

Categories | category
Tags | post_tag


### Post Meta

All detected post meta keys are automatically exported as additional CSV columns.

Some internal WordPress meta keys are excluded by default.

---

## Export Location

Exports are saved to:
`/wp-content/uploads/squatch-exports/`


Each export file is named:
`{post-type}-export-YYYY-MM-DD_HH-MM-SS.csv`


Example:
`post-export-2026-03-14_15-20-44.csv`


---

## Requirements

- WordPress 5+
- PHP 7+

---

## Author

**Squatch Creative**
https://squatchcreative.com

---

## Repository

https://github.com/RCNeil/squatch-post-exporter
