# Dict

Oxford Dictionaries

### Install

```bash
mkdir -p var/ssl
openssl genrsa -out var/ssl/server.key 2048
openssl req -new -key var/ssl/server.key -out var/ssl/server.csr
openssl x509 -req -days 365 -in var/ssl/server.csr -signkey var/ssl/server.key -out var/ssl/server.crt
cat var/ssl/server.crt var/ssl/server.key > var/ssl/server.pem
docker-compose build
```

# API Credentials

```bash
cp .env .env.local
```
Put the API Credentials from [Oxford Dictionaries](https://developer.oxforddictionaries.com/) into the file .env.local parameters: APP_DICT_APP_ID and APP_DICT_APP_KEY

## Usage

```bash
docker-compose up -d
```

Open [http://localhost/](http://localhost/)
