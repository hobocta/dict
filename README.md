# Dict

Oxford Dictionaries

### Install

```bash
docker-compose up -d && \
    docker-compose exec php composer install
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
