FWP.deprecated = (old_method, new_method, ...args) => {
    console.warn('FWP.' + old_method + '() has changed to FWP.' + new_method + '()');
    return FWP[new_method](...args);
};
FWP.build_post_data = (...args) => FWP.deprecated('build_post_data', 'buildPostData', ...args);
FWP.build_query_string = (...args) => FWP.deprecated('build_query_string', 'buildQueryString', ...args);
FWP.fetch_data = (...args) => FWP.deprecated('fetch_data', 'fetchData', ...args);
FWP.load_from_hash = (...args) => FWP.deprecated('load_from_hash', 'loadFromHash', ...args);
FWP.parse_facets = (...args) => FWP.deprecated('parse_facets', 'parseFacets', ...args);
FWP.set_hash = (...args) => FWP.deprecated('set_hash', 'setHash', ...args);
