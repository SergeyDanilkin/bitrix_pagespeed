find . -type f \( -iname "*.jpg" -o -iname "*.jpeg" \) -exec jpegoptim --strip-all --all-progressive -pm100 {} \;
find -type f -iname "*.png" -exec optipng -strip all -o4 {} \;
