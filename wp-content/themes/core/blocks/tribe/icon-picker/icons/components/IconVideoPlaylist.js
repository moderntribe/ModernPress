import * as React from 'react';
const SvgIconVideoPlaylist = ( props ) => (
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
			d="M11 12h42M17 6h30M55 18H9a5 5 0 0 0-5 5v30a5 5 0 0 0 5 5h46a5 5 0 0 0 5-5V23a5 5 0 0 0-5-5Z"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="m28 30 12 8-12 8z"
		/>
	</svg>
);
export default SvgIconVideoPlaylist;
