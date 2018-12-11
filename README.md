# Traefik dumpcerts
Docker container for exporting acme certificates from traefik's `acme.json` file. It uses slightly modified [script from traefik's repository](https://github.com/containous/traefik/blob/master/contrib/scripts/dumpcerts.sh) to export certificates to several pem formats and therefore enables other software use certificates obtained by Traefik.
Certificates are exported on container start and then again every day.

## Volumes
### /etc/ssl/acme/src
Here should be the `acme.json` file mounted, so that the final path of the file is `/etc/ssl/acme/src/acme.json`. Read-only permissions are enough.

### /etc/ssl/acme/dst
Directory must have write permissions. This is the output direcotry, where all certificates are exported in following structure:
* _domain.name_
  * `ca.crt` CA certificate chain
  * `domain.crt` Domain certificate
  * `domain.key` Domain private key
  * `domain.pem` Domain bundle of all: key, certificate and CA chain
  * `chainn.crt` Domain bundle of certificates: certificate and CA chain
