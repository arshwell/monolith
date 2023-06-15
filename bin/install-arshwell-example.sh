## Unarchive file: monolith/example.tar.gz in root of project ##

echo "";
echo "Copying the example in your project ⇩ Good luck!"
echo "";

BASEDIR=$(dirname $0)
MONOLITH=$(dirname $BASEDIR) # monolith path

tar --strip-components=1 -C "/" -xf "${MONOLITH}/example.tar.gz"
