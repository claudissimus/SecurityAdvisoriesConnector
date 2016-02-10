# SecurityAdvisoriesConnector
Access the SensioLabs Security Advisories Checker from PHP

## Usage
Just type `php check.php`. The script will then upload the composer.lock file from the same directory to the service and output with its result to stdout.
Alternatively you can use `php check.php <path-to-composer.lock>`

## Exit codes
0: No issues
>0: Issue count
<0: Error