/**
 * Essentially this is a copy of https://www.npmjs.com/package/gulp-clean-css
 * However, that package is 4 years old and locked to clean-css@4.x which is buggy as hell
 * This file exists purely so we can use clean-css@^5.3
 */

import applySourceMap from 'vinyl-sourcemaps-apply';
import CleanCSS from 'clean-css';
import path from 'path';
import PluginError from 'plugin-error';
import through from 'through2';

export default (options, callback) => {

  let _callback = callback || (o => undefined);

  return through.obj(function (file, enc, cb) {

    let _options = Object.assign({}, options || {});

    if (file.isNull()) {
      return cb(null, file);
    }
    if (file.isStream()) {
      this.emit('error', new PluginError('gulp-clean-css', 'Streaming not supported!'));
      return cb(null, file);
    }

    if (file.sourceMap) {
      _options.sourceMap = JSON.parse(JSON.stringify(file.sourceMap));
    }

    const content = {
      [file.path]: {styles: file.contents ? file.contents.toString() : ''}
    };
    if (!_options.rebaseTo && _options.rebase !== false) {
      _options.rebaseTo = path.dirname(file.path);
    }

    new CleanCSS(_options).minify(content, (errors, css) => {

      if (errors) {
        return cb(errors.join(' '));
      }

      let details = {
        'stats': css.stats,
        'errors': css.errors,
        'warnings': css.warnings,
        'path': file.path,
        'name': file.path.split(file.base)[1]
      };

      if (css.sourceMap) {
        details['sourceMap'] = css.sourceMap;
      }
      _callback(details);

      file.contents = new Buffer.from(css.styles);

      if (css.sourceMap) {
        const iMap = JSON.parse(css.sourceMap);
        const oMap = Object.assign({}, iMap, {
          file: path.relative(file.base, file.path),
          sources: iMap.sources.map(mapSrc => path.relative(file.base, mapSrc))
        });
        applySourceMap(file, oMap);
      }
      cb(null, file);
    });
  });
};
