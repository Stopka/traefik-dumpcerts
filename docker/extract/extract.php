#!/usr/bin/php
<?php

const SOURCE = '/etc/ssl/acme/src/acme.json';
const DESTINATION = '/etc/ssl/acme/dst';

function write(string $msg, bool $stderr = false): void
{
    fwrite($stderr ? STDERR : STDOUT, $msg);
}

function writeln(string $msg, bool $stderr = false): void
{
    write("{$msg}\n", $stderr);
}

function err(string $msg): void
{
    writeln($msg);
    exit(1);
}

function readData(): array
{
    $source = SOURCE;
    writeln("Reding file '{$source}'");
    $file = @file_get_contents($source);
    if (false === $file) {
        err("Unable to read from file '{$source}'");
    }
    $data = json_decode($file, true);
    if (false === $data) {
        err("Unable to parse json");
    }
    return $data;
}

function getPath(string $mainDomain, string $file = '')
{
    return DESTINATION . "/{$mainDomain}{$file}";
}

function makeDomainDir(string $mainDomain)
{
    $domainDir = getPath($mainDomain);
    if (file_exists($domainDir)) {
        if (is_dir($domainDir)) {
            writeln("Dir '{$domainDir}' already exists");
            return;
        }
        err("Filename {$domainDir} is already taken");
    }
    writeln("Creating dir '{$domainDir}' recursively");
    $result = @mkdir($domainDir, 0777, true);
    if (false === $result) {
        err("Unable to create dir '{$domainDir}'");
    }
}

function exportFile(string $mainDomain, string $file, string $content)
{
    $filePath = getPath($mainDomain, $file);
    writeln("Writing to file '{$filePath}'");
    $result = @file_put_contents($filePath, $content);
    if (false === $result) {
        err("Unable to write to file '{$filePath}'");
    }
}

function exportPem(string $mainDomain, array $chain, string $key)
{
    writeln("Exporting full domain cert chain with key file");
    array_push($chain, $key);
    exportFile($mainDomain, '/domain.pem', implode("\n", $chain));
}

function exportFullChain(string $mainDomain, array $chain)
{
    writeln("Exporting full domain cert chain file");
    exportFile($mainDomain, '/chain.crt', implode("\n", $chain));
}

function exportCert(string $mainDomain, string $cert)
{
    writeln("Exporting domain cert file");
    exportFile($mainDomain, '/domain.crt', $cert);
}

function exportKey(string $mainDomain, string $key)
{
    writeln("Exporting domain key file");
    exportFile($mainDomain, '/domain.key', $key);
}

function exportCaChain(string $mainDomain, array $caChain)
{
    writeln("Exporting domain ca chain file");
    exportFile($mainDomain, '/ca.crt', implode("\n", $caChain));
}

function exportAll(string $mainDomain, array $chain, string $key): void
{
    writeln("Exporting all of '{$mainDomain}'");
    makeDomainDir($mainDomain);
    exportPem($mainDomain, $chain, $key);
    exportFullChain($mainDomain, $chain);
    $cert = array_shift($chain);
    exportCert($mainDomain, $cert);
    exportCaChain($mainDomain, $chain);
    exportKey($mainDomain, $key);
}

function explodeChain(string $certificate): array
{
    $certificate = str_replace('-----END CERTIFICATE-----', "-----END CERTIFICATE-----\n\n", $certificate);
    return preg_split('/[\n]{2,}/', $certificate, -1, PREG_SPLIT_NO_EMPTY);
}

function processCertificate(string $mainDomain, string $encodedCertificate, string $encodedKey): void
{
    $certificate = base64_decode($encodedCertificate, true);
    if (false === $certificate) {
        err("Unable to decode certificate for {$mainDomain}");
    }
    $key = base64_decode($encodedKey, true);
    if (false === $key) {
        err("Unable to decode key for {$mainDomain}");
    }
    $chain = explodeChain($certificate);
    if (0 === count($chain)) {
        err("Certificate chain for {$mainDomain} is empty");
    }
    exportAll($mainDomain, $chain, $key);
}

function processCertificates(array $certificates): void
{
    foreach ($certificates as $index => $certificate) {
        if (!isset($certificate['domain']['main'])) {
            err("Missing main domain in certificate #{$index}");
        }
        $mainDomain = $certificate['domain']['main'];
        if (!isset($certificate['certificate'])) {
            err("Missing certificate data for certificate #{$index} {$mainDomain}");
        }
        if (!isset($certificate['key'])) {
            err("Missing key data for certificate #{$index} {$mainDomain}");
        }
        writeln("Processing certificate #{$index} {$mainDomain}");
        processCertificate($mainDomain, $certificate['certificate'], $certificate['key']);
    }
}

function processResolvers(array $resolvers): void
{
    foreach ($resolvers as $resolverKey => $resolver) {
        writeln("Processing resolver '{$resolverKey}'");
        if (!is_array($resolver['Certificates'])) {
            err("Missing certificate list in resolver '{$resolverKey}'");
        }
        processCertificates($resolver['Certificates']);
    }
}

writeln("Certificate bundle export started");
processResolvers(readData());
writeln("Done");

