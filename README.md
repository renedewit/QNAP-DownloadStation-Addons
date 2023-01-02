# QNAP-DownloadStation-Addons
Updated add-ons for old DownloadStation v5.0. (which can't be updated on older NAS-devices with QTS 4.3.3)

Installation
============
1. Requires a signed package (.addon). Note: DO NOT unzip the release file.
2. Open Download Station -> Settings (... menu) -> Add-ons.
3. If the addon already exists delete it first (select it -> waste bin button).
4. Click add  (+) Button and select the downloaded release file.

Download other releases from:
https://github.com/kimiazhu/qts-download-station-addon/releases
https://github.com/dokkis/qnap-torrent-providers/releases

Manual installation:
====================
1. Manually upload the content of the "addons" folders to the corresponding folder on your NAS. Example command:
cp /share/MD0_DATA/Public/[torrent foldername]/* /share/MD0_DATA/.qpkg/DSv3/usr/sbin/addons/[torrent foldername]/

2. Open your Download Station app on your NAS, go to Settings, and click the refresh button in the "Add-ons" tab. It will load the newly uploaded add-ons.

3. Make sure you enable them if you plan to use them.

Manual run & debug:
===================
You can run these addons from your terminal from your NAS. Follow the addon developer guide to identify where your addons are located, usually /share/MD0_DATA/.qpkg/DSv3/usr/sbin/. Go to the sbin folder and run:

./ds-addon -s [addon foldername] [search string] [result limit]

So, for example you could run:

./ds-addon -s 1337x.to [search string] 2

Create signed package:
======================

If you have never generated an RSA key before, please follow these steps to make a new one. Start in the sbin folder (usually: /share/MD0_DATA/.qpkg/DSv3/usr/sbin):

1. Generate private rsa key:
/usr/bin/openssl genrsa -out private.pem 1024

2. Generate public rsa key from the private one:
/usr/bin/openssl rsa -in private.pem -out public.pem -outform PEM -pubout

Both keys should be present in the sbin folder (check with: ls -al command).

3.And next, use ds-addon-pack.sh and RSA key to compress add-ons and make a signature:
./ds-addon-pack.sh private.pem public.pem addons/[torrent foldername]

This will generate a package "[torrent foldername][version-timestamp].addon" in the sbin folder.
