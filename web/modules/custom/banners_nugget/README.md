# Banners Nugget with banner nugglet

Provides Banners nugget with required fields

## Installation

1. Add the repository to composer

```json
    {
  "name": "mrm-remora/banners_nugget",
  "type": "vcs",
  "url": "git@github.com:MRM-Remora/banners_nugget.git",
  "no-api": true
}
```

2. Run `composer require mrm-remora/banners_nugget "^1.0"`
3. Enable the module by running `drush pm:e banners_nugget`
4. Export config with `drush cex` and commit

## Styling

Currently there are no variables in Figma for the Banner.

_style.scss contains basic styles and !default variables which can be overwritten in the subtheme. To override the mixin for palettes, add the following to a /components/_banner.scss file in subtheme:
```
@import '../../../../../../modules/contrib/banners_nugget/scss/style.scss';

.paragraph.paragraph--type--banners {
  @include banner(
      $background-color,
      $title-colour,
      $body-color,
  );
}
```