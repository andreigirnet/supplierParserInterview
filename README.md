# Project Name

PHP script for parsing supplier data of different formats: csv,json,xml
The script is grabbing each row and is grouping products by count in a new file of the same type.

## Installation

git pull

## Usage
Run this in your terminal, you gave to be in the root folder.

php parser.php --file products.csv --unique-combinations=combination_count.csv --format=csv

php parser.php --file products.xml --unique-combinations=combination_count.xml --format=xml

php parser.php --file products.json --unique-combinations=combination_count.json --format=json

Note: You must have the files "products and combinations_count" created in the root folder
