import * as React from 'react';
const SvgIconStackLayers = ( props ) => (
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
			d="M56.498 30 60 32 32 48 4 32l3.5-2"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M56.51 42.006 60 44 32 60 4 44l3.5-2M4 20 32 4l28 16-28 16z"
		/>
	</svg>
);
export default SvgIconStackLayers;
