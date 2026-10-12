#!/usr/bin/env python3
"""
Local dev server for DrofLove.com.
Serves static files directly; routes *.php through php-cgi.exe via CGI protocol.
"""
import os, sys, subprocess, urllib.parse

DOC_ROOT = os.path.dirname(os.path.abspath(__file__))
PHP_CGI = r"C:\Users\WEL\php-dl\php83\php-cgi.exe"

def guess_type(path):
    ext = os.path.splitext(path)[1].lower()
    return {
        ".css": "text/css",
        ".js": "application/javascript",
        ".png": "image/png",
        ".jpg": "image/jpeg",
        ".jpeg": "image/jpeg",
        ".gif": "image/gif",
        ".svg": "image/svg+xml",
        ".woff": "font/woff",
        ".woff2": "font/woff2",
        ".ico": "image/x-icon",
        ".webp": "image/webp",
        ".json": "application/json",
        ".txt": "text/plain",
    }.get(ext, "application/octet-stream")

def serve_php(file_path, query_string, environ):
    """Run php-cgi.exe with CGI env, return (status, headers, body)."""
    env = dict(os.environ)
    env.update({
        "REQUEST_METHOD": environ.get("REQUEST_METHOD", "GET"),
        "QUERY_STRING": query_string,
        "REQUEST_URI": environ.get("REQUEST_URI", "/"),
        "SCRIPT_NAME": "/" + os.path.relpath(file_path, DOC_ROOT).replace("\\", "/"),
        "SCRIPT_FILENAME": file_path,
        "DOCUMENT_ROOT": DOC_ROOT,
        "GATEWAY_INTERFACE": "CGI/1.1",
        "SERVER_NAME": "127.0.0.1",
        "SERVER_PORT": "8088",
        "SERVER_PROTOCOL": "HTTP/1.1",
        "SERVER_SOFTWARE": "HermesDevServer/1.0",
        "REMOTE_ADDR": "127.0.0.1",
        "REDIRECT_STATUS": "200",
        "PHPRC": os.path.dirname(PHP_CGI),
    })
    if environ.get("REQUEST_METHOD", "GET") == "POST":
        length = int(environ.get("CONTENT_LENGTH", 0))
        env["CONTENT_LENGTH"] = str(length)
        env["CONTENT_TYPE"] = environ.get("CONTENT_TYPE", "")

    proc = subprocess.Popen(
        [PHP_CGI],
        env=env,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
    )
    # Feed POST body if needed
    stdin_data = None
    if environ.get("REQUEST_METHOD") == "POST":
        length = int(environ.get("CONTENT_LENGTH", 0))
        if length > 0:
            stdin_data = environ["wsgi.input"].read(length) if "wsgi.input" in environ else None

    out, err = proc.communicate(input=stdin_data)
    output = out.decode("utf-8", errors="replace")

    # Split headers and body
    if "\r\n\r\n" in output:
        header_part, body = output.split("\r\n\r\n", 1)
    elif "\n\n" in output:
        header_part, body = output.split("\n\n", 1)
    else:
        header_part, body = "", output

    status = 200
    headers = []
    for line in header_part.splitlines():
        if line.lower().startswith("status:"):
            status = int(line.split(":", 1)[1].strip().split()[0])
        elif ":" in line:
            k, v = line.split(":", 1)
            headers.append((k.strip(), v.strip()))

    return status, headers, body.encode("utf-8", errors="replace")

from http.server import HTTPServer, BaseHTTPRequestHandler

class Handler(BaseHTTPRequestHandler):
    def _handle(self):
        parsed = urllib.parse.urlparse(self.path)
        path = parsed.path
        query = parsed.query

        # Map / to index.php
        if path == "/":
            path = "/index.php"

        # Strip leading slash, convert to filesystem path
        rel = path.lstrip("/")
        file_path = os.path.join(DOC_ROOT, rel)

        # Security: no traversal
        if ".." in rel:
            self.send_error(403, "Forbidden")
            return

        if os.path.isdir(file_path):
            # Try index.php
            file_path = os.path.join(file_path, "index.php")

        if not os.path.exists(file_path):
            # Try appending .php
            if os.path.exists(file_path + ".php"):
                file_path = file_path + ".php"
            else:
                self.send_error(404, "Not Found")
                return

        ext = os.path.splitext(file_path)[1].lower()

        if ext == ".php":
            environ = {
                "REQUEST_METHOD": self.command,
                "QUERY_STRING": query,
                "REQUEST_URI": self.path,
            }
            if self.command == "POST":
                length = int(self.headers.get("Content-Length", 0))
                environ["CONTENT_LENGTH"] = str(length)
                environ["CONTENT_TYPE"] = self.headers.get("Content-Type", "")
                environ["wsgi.input"] = self.rfile

            status, headers, body = serve_php(file_path, query, environ)

            self.send_response(status)
            for k, v in headers:
                if k.lower() not in ("transfer-encoding", "content-length"):
                    self.send_header(k, v)
            self.send_header("Content-Length", str(len(body)))
            self.end_headers()
            self.wfile.write(body)
        else:
            try:
                with open(file_path, "rb") as f:
                    content = f.read()
                self.send_response(200)
                self.send_header("Content-Type", guess_type(file_path))
                self.send_header("Content-Length", str(len(content)))
                self.end_headers()
                self.wfile.write(content)
            except Exception as e:
                self.send_error(500, str(e))

        return

    def do_GET(self):
        self._handle()

    def do_POST(self):
        self._handle()

    def log_message(self, *args):
        pass  # quiet

if __name__ == "__main__":
    port = 8088
    httpd = HTTPServer(("0.0.0.0", port), Handler)
    print(f"DrofLove.com dev server on http://127.0.0.1:{port}")
    httpd.serve_forever()
