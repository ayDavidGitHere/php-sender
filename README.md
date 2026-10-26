# PHP Sender

`sender.php` is a browser-based file and text sharing tool hosted by a PHP server. It works without JavaScript.

## Features

- Browse directories and download individual files.
- Search by filename prefix, ignoring case.
- Download folders as ZIP archives. Symbolic links are skipped.
- Upload files to the machine running the server.
- Update and view shared text in `senderphpfiles/shared.txt`.
- Preview MP3 audio, MP4 video, and PNG/JPG/JPEG images.

## Requirements

- PHP with command-line support.
- The PHP ZIP extension (`ZipArchive`) for folder downloads.
- Write permission in the serving directory for uploads and shared text, and in the system temporary directory for ZIP creation.
- A network connection between the server and client devices.

## How to run

1. Place `sender.php` in the directory containing the files you want to share.
2. Find the server's local IP address. On Linux, run:

   ```sh
   ip addr
   ```

3. Start the server from the sharing directory, replacing `<ip>` with that address and `<port>` with an available port:

   ```sh
   cd <dir>
   php -S <ip>:<port>
   ```

4. On another device that can reach the server, open `http://<ip>:<port>/sender.php` in a browser.

For example, `php -S 192.168.0.3:9000` serves the page at `http://192.168.0.3:9000/sender.php`.

### Optional upload configuration

A custom `php.ini` is not necessary, but can be useful for changing upload limits. If you use the included file, place it in the serving directory and start PHP with:

```sh
php -S <ip>:<port> -c php.ini
```

Set `upload_max_filesize` to the maximum individual file size and `post_max_size` high enough to include the file and form data. Review the included values before use; they allow very large requests.

## Uploads and shared text

Uploads are saved in `senderphpfiles/`, which is created when needed. Supported upload extensions are TXT, CSV, JSON, PDF, ZIP, PNG, JPG/JPEG, GIF, WebP, MP3, and MP4. Executable filenames, including PHP double extensions such as `example.php.jpg`, are rejected. These checks validate filenames, not file contents.

The shared-text form replaces `senderphpfiles/shared.txt`. Its contents are displayed as escaped text, and the page reports whether an update succeeds or fails.

## Current limitations

- There is no authentication or CSRF protection. Anyone who can reach the server can browse, upload, and change shared text. Use it only on a trusted network.
- Directory browsing is not restricted to the sharing directory; directory parameters can reach other paths readable by the PHP process.
- Uploading a file with an existing name can overwrite it.
- PHP's built-in server is intended for development and local use. Do not expose this setup directly to the public internet.
