# Invoice System

## FPDF Library Installation

This system requires the FPDF library for PDF generation. Follow these steps to install it:

1. Download FPDF from [http://www.fpdf.org/](http://www.fpdf.org/)
2. Extract the contents to the `fpdf186` folder in your project
3. Make sure the file `fpdf186/fpdf.php` exists

### Quick Installation (Windows)

1. Download the FPDF library:
   - Visit [http://www.fpdf.org/en/download.php](http://www.fpdf.org/en/download.php)
   - Download the latest version (1.86)

2. Extract the contents:
   - Extract the downloaded zip file
   - Copy all files to the `fpdf186` folder in your project

### Quick Installation (Linux/Mac)

```bash
# Create the directory
mkdir -p fpdf186

# Download FPDF
wget -O fpdf.zip http://www.fpdf.org/en/dl.php?v=186

# Extract to the fpdf186 directory
unzip fpdf.zip -d fpdf186

# Clean up
rm fpdf.zip
```

## Database Setup

Make sure your database connection is properly configured in `config/db_connect.php`. 