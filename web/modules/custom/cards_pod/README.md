# Cards Pod module

Provides functionality of referencing other CTs as cards.

This module depends on remora_core, however there is a bit of a circular reference. RBT also requires this module, but can't because it's a theme. That's why remora_core requires this module, and this module implicitly requires remora_core.  

## Pod field/region availability
### Cards
- Main content
- Sidebar content
- Postscript content

### Card pea
- Cards pod

## Hooks
| Name                           | Runs when?                | Short description                                                                            |
|--------------------------------|---------------------------|----------------------------------------------------------------------------------------------|
| cards_pod_form_alter        | Add/edit forms are loaded | Adds conditional logic for customise card fields.<br/>Overrides deafult link field help text |
| cards_pod_custom_validation | Forms are saved           | Validates fields are set based on the presentation field for the cards pod                |
