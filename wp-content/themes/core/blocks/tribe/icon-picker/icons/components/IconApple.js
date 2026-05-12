import * as React from 'react';
const SvgIconApple = ( props ) => (
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
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M32 13.734C32 8.21 33.124 4.334 38 2"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M55.058 14.95a13.6 13.6 0 0 0-15.2-4.11L32 13.734l-7.854-2.894a13.6 13.6 0 0 0-15.2 4.11 13.02 13.02 0 0 0-2.778 10.296l4.38 22.458a10.02 10.02 0 0 0 13.938 7.442L32 51.868l7.514 3.278a10.018 10.018 0 0 0 13.938-7.442l4.384-22.458a13.02 13.02 0 0 0-2.778-10.296Z"
		/>
	</svg>
);
export default SvgIconApple;
