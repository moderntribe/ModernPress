# Rule Processing

Rules are processed via a Pipeline design pattern.

1. The Pipeline processes the different Factories to determine which Processor instance to create.
2. The created Processor then determines if the Alert Banner should be displayed based on the configured criteria the user selected in the active Alert.
3. These are initiated from the [Alert Rules Pipeline](../Components/Alert/Rules).

### [Factories](Factories)

Each factory is run through a Pipeline to determine which Processor should be created. Factories are run in a very specific order and if the conditions to create Processor are not met, it passes the data onto the next stage in the Pipeline until the proper Processor is created or all stages have been run.

A Factory's only responsibility is to see if a certain Processor should be created or not.


### [Processors](Processors)

Processors perform a check based on the rules that are configured in the dashboard for the current active Alert. 

For example, The [Post_Type_Archive_Processor](Processors/Post_Type_Archive_Processor.php) determines if the user had selected the currently viewed Post Type Archive to be included in the rule set, which will ultimately determine if the Alert Banner will display for the user or not.
