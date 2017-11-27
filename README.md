# Generic Faker
A KBC component which takes tables and creates fake JSON responses for [Mock server](https://github.com/keboola/ex-generic-mock-server). 
The responses are pushed into [git repository](https://github.com/keboola/app-generic-faker-data) which takes care of running the mock server.

## Configuration
Configuration takes any number of tables. Dot in file name will be converted to slash in target mock server URL. Therefore, do not specify file extension
e.g.:

- `coords` (`/data/in/tables/coords`) -- will be available at `/coords/`
- `location.coords` (`/data/in/tables/location.coords`) -- will be available at `/location/coords/`

![Screenshot - Configuration](https://github.com/keboola/app-generic-faker/blob/master/doc/configuration.png)

Configuration accepts credentials required for HTTP authentication of mock server requests:

```
{
  "username": "user",
  "#password": "secret"
}
```

## Running Locally
Create standard data folder with input tables and config file in `./data`. Run `docker-compose run app-generic-faker` or `docker-compose run app-generic-faker-dev` for local development (generates `vendor` locally. Config file also requires `image_parameters` section with SSH address and key to the git repository:

```
"image_parameters": {
    "repository": "git@github.com:keboola/app-generic-faker-data.git",
    "#git_key": "-----BEGIN RSA PRIVATE KEY-----\nMII...4Zc=\n-----END RSA PRIVATE KEY-----\n"
}
```
