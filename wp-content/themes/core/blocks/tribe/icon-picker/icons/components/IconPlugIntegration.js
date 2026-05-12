import * as React from 'react';
const SvgIconPlugIntegration = ( props ) => (
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
			d="m4 60 8.002-8.002M28 28l-6 6M36 36l-6 6M16.338 28.338 12 32.672A13.667 13.667 0 0 0 31.328 52l4.334-4.336zM60 4l-8.002 8.002M47.662 35.662 52 31.328A13.667 13.667 0 1 0 32.672 12l-4.334 4.338z"
		/>
	</svg>
);
export default SvgIconPlugIntegration;
