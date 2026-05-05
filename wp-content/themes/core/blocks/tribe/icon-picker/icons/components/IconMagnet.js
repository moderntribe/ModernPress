import * as React from 'react';
const SvgIconMagnet = ( props ) => (
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
			d="m21.4 11 8 7.8M45.2 34.6l7.8 8M33.2 46.6c-4.4 4.4-11.4 4.4-15.8 0s-4.4-11.4 0-15.8L37.2 11l-8-8L9.4 22.8c-8.8 8.8-8.8 23 0 31.6s23 8.8 31.6 0l19.8-19.8-8-8z"
		/>
	</svg>
);
export default SvgIconMagnet;
