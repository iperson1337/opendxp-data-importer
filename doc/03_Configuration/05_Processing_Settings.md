# Processing Settings

There are a couple of processing settings that allow fine-tuning the import process.

### Execution Type
There are two types of processing - sequential and parallel - which are configured in the import configuration.
For details see [Import Execution Details](../04_Import_Execution_Details.md). 

### Archive File Import
Define if the importer should archive the imported file for later traceability. When activated, 
the importer will create an application logger entry with the imported file as file object attached. 

Be aware of needed disk space for archiving the data and that the importer creates a log entry of every 
single data row in application logger anyway. For details also see 
[Import Process and Logging](../05_Import_Progress_and_Logging.md).
   

### Skip Mandatory Field Check
Data objects only: saves imported objects with `setOmitMandatoryCheck(true)`. Use it for imports that update a few
fields of existing objects — otherwise an empty mandatory field that the import does not touch rejects the row with
`Validation failed: Empty mandatory field [...]`. YAML key: `processingConfig.omitMandatoryCheck`.

### Reject Objects with a Newer Draft
Data objects only: rejects the row (error in the import log, object not saved) if the existing object has a version
newer than the saved one, i.e. an unpublished draft. Saving the object would leave the draft as the latest version,
and publishing that draft later silently reverts the imported values. YAML key: `processingConfig.rejectIfNewerDraft`.

### Object Condition
Data objects only: name of a checking method of the existing object (`is*`, `has*` or `can*`, no arguments), e.g.
`isShowboxLevelAvailable`. If it does not return `true`, the row is rejected with the configured reason (or a generic
one). The condition is checked before mapping, i.e. on the saved state of the object. Other method names are refused
as a configuration error. YAML keys: `processingConfig.elementCondition`, `processingConfig.elementConditionMessage`.

### Delta Check
> This option requires an id field in the import data.

With active delta check, the importer checks if imported data has changed since last import and imports 
data only if updates are available. This might speed-up import because only updated data object will be
processed and saved. 

The delta check is based on hashes and compares the import data only. It does not consider any other 
changes of the data object if self, that might occur in the meantime between two imports.

Be aware that calculating and saving hashes for delta checking needs additional resources. So use this option
wisely.   


### Reject Duplicate IDs
> This option requires an id field in the import data.

Rejects every row whose id value occurs in the import file more than once — otherwise the row processed last would
win silently. Duplicates are counted over the whole queue right after the file is interpreted.
YAML key: `processingConfig.rejectDuplicateIds`.


### Cleanup   
> This option requires an id field in the import data.

The importer can cleanup in OpenDXP existing data objects if they are not part of the current import data
anymore. There are two cleanup strategies available: 
- Unpublish: Unpublish data object to be cleaned up. 
- Delete: Delete data object to be cleaned up.

Only activate this option, if the imports are always full-imports. Otherwise there might be data loss. 

For details see also [Import Execution Details](../04_Import_Execution_Details.md). 
