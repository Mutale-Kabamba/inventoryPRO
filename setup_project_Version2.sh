#!/bin/bash

# Root directories
mkdir -p config includes public assets

# Feature modules
for dir in users branches products sales purchases expenses transfers stock reports; do
  mkdir -p "$dir"
done

# Users module
touch users/{list.php,add.php,edit.php,delete.php}
# Branches module
touch branches/{list.php,add.php,edit.php,delete.php}
# Products module
touch products/{list.php,add.php,edit.php,delete.php}
# Sales module
touch sales/{list.php,add.php}
# Purchases module
touch purchases/{list.php,add.php}
# Expenses module
touch expenses/{list.php,add.php}
# Transfers module
touch transfers/{list.php,add.php}
# Stock module
touch stock/view.php
# Reports module
touch reports/{dashboard.php,sales.php,expenses.php,stock.php}

# Config & includes
touch config/db.php
touch includes/{auth.php,header.php,footer.php}

# Public
touch public/{index.php,login.php,logout.php,dashboard.php,style.css}

# Assets
touch assets/.gitkeep

# SQL and docs
touch init.sql README.md

echo "Project structure created!"