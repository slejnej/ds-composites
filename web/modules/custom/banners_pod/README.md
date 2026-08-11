# Banners Pod with banner pea

Provides Banners pod with required fields

## Installation

1. Add the repository to composer

```json
    {
  "name": "mrm-remora/banners_pod",
  "type": "vcs",
  "url": "git@github.com:MRM-Remora/banners_pod.git",
  "no-api": true
}
```

2. Run `composer require mrm-remora/banners_pod "^1.0"`
3. Enable the module by running `drush pm:e banners_pod`
4. Export config with `drush cex` and commit

## Styling

Currently there are no variables in Figma for the Banner.

_style.scss contains basic styles and !default variables which can be overwritten in the subtheme. To override the mixin for palettes, add the following to a /components/_banner.scss file in subtheme:
```
@import '../../../../../../modules/custom/banners_pod/scss/style.scss';

.paragraph.paragraph--type--banners {
  @include banner(
      $background-color,
      $title-colour,
      $body-color,
  );
}
```