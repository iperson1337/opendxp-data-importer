# OpenDXP Data Importer

***

## Disclaimer

> OpenDXP is a community-driven fork based on the Pimcore® Community Edition (GPLv3).  
> OpenDXP is independent and maintained by its community and contributors.
> It is not affiliated with, endorsed by, or sponsored by Pimcore GmbH.   
> Original credits: [Pimcore GmbH](https://www.pimcore.com)

**OpenDXP DataImporter Bundle is based on the Pimcore® Community Edition and remains licensed under GPLv3.**

***

This extension adds a comprehensive import functionality to OpenDXP Datahub. It allows importing data from external 
sources and adjusting it to OpenDXP Data Objects based on a configured mapping without writing any code.

## Features in a Nutshell
- Multiple imports configuration directly in Datahub. 
- Data import from various data sources.
- Supported File Formats: `csv`, `xlsx`, `json`, `xml`.
- Strategies configuration for: 
  - loading existing elements for updating data.
  - defining location for newly imported data.
  - publishing data.
  - cleanup of existing data. 
-  Mappings definition for adjusting data to OpenDXP Data Objects with:
   - simple transformations.
   - preview of imported data.
- Imports execution directly in OpenDXP Datahub or on a regular base via cron definitions. 
- Import status updates and extensive logging information. 

## Documentation Overview
- [Installation](./doc/01_Installation.md)
- [Configuration](./doc/03_Configuration/README.md)
- [Import Execution Details](./doc/04_Import_Execution_Details.md)
- [Import Progress and Logging](./doc/05_Import_Progress_and_Logging.md)
- [Extending](./doc/06_Extending/README.md)
- [Troubleshooting/FAQ](./doc/06_Troubleshooting_FAQ.md) 

## Further Information
On other OpenDXP Datahub adapters and export solutions:
- [Datahub (GraphQL API)](https://docs.opendxp.io/docs/core-bundles/data-hub/Installation_and_Upgrade/)

***

## Upstream Origin & Version Transparency
This project is a fork of the [Pimcore data-importer (685c7c1 / v1.10.2)](https://github.com/pimcore/data-importer/tree/685c7c1e29b2c59c9883036fcc964dd5fa9bc12a), which is © Pimcore GmbH and licensed under GPLv3.

## License
Licensed under the GNU General Public License v3.0 (GPLv3). For details, please see [LICENSE.md](LICENSE.md).

## Copyright
© Pimcore GmbH  
© 2026 OpenDXP Contributors — GPLv3

## Trademarks
Pimcore® is a registered [trademark](https://www.trademarkelite.com/europe/trademark/trademark-detail/009309841/PIMCORE) of Pimcore GmbH.
Any use of the Pimcore® mark in this repository is purely descriptive to identify the original upstream project.

***

## Contact
For inquiries, suggestions, or contributions, feel free to reach us at contact@opendxp.io.

## About
OpenDXP is a community-driven project initiated by [DACHCOM.DIGITAL](https://www.dachcom.com/de-ch) (Rheineck, Switzerland) and maintained by its community and contributors.
OpenDXP is independent and not affiliated with Pimcore GmbH.

The project’s purpose is to preserve and maintain a GPLv3‑licensed codebase for community use.

It is **not positioned as a competitor** to products or services of Pimcore GmbH and does **not** purport to replace or supersede any Pimcore offering.   
