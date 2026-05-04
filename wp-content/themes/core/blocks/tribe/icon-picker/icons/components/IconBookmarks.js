import * as React from 'react';
const SvgIconBookmarks = ( props ) => (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		width="1em"
		height="1em"
		fill="none"
		viewBox="0 0 64 64"
		{ ...props }
	>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M50 60 28 46 6 60V16a6 6 0 0 1 6-6h32a6 6 0 0 1 6 6z"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M20 2h32a6 6 0 0 1 6 6v44"
		/>
	</svg>
);
export default SvgIconBookmarks;
