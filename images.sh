find -type f -iname "*.jpg" -exec jpegoptim --strip-all --all-progressive -pm85 {} \;
find -type f -iname "*.png" -exec optipng -strip all -o4 {} \;