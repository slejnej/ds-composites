# Remora sections
This module contains the config for the below sections:
- Footer site settings
- Flexible layout
- Section

## Dependencies
This module depends on remora_core for its entity reference handler. There is no point in moving the handler into this project since the implementing class depends on remora_core functionality.

### MRM packages
| MRM package               | Version | Extended README                                                         |
|---------------------------|---------|-------------------------------------------------------------------------|
| mantaraymedia/remora_core | ^v1.0   | [open](https://github.com/MRM-Remora/remora_core/blob/master/README.md) |

### Hooks
| Name                   | Runs when?                                                      | Short description                                       |
|------------------------|-----------------------------------------------------------------|---------------------------------------------------------|
| paragraph_presave      | Runs when a sidebar section has been edited and the node saved. | Replaces the value of field_palette with 'palette-alt-1 |
