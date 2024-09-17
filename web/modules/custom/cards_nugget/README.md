# Cards Nugget module

Provides functionality of referencing other CTs as cards.

This module depends on remora_core, however there is a bit of a circular reference. RBT also requires this module, but can't because it's a theme. That's why remora_core requires this module, and this module implicitly requires remora_core.  

## Nugget field/region availability
### Cards
- Main content
- Sidebar content
- Postscript content

### Card nugglet
- Cards nugget

## Hooks
| Name                           | Runs when?                | Short description                                                                            |
|--------------------------------|---------------------------|----------------------------------------------------------------------------------------------|
| cards_nugget_form_alter        | Add/edit forms are loaded | Adds conditional logic for customise card fields.<br/>Overrides deafult link field help text |
| cards_nugget_custom_validation | Forms are saved           | Validates fields are set based on the presentation field for the cards nugget                |
