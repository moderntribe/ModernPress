import * as React from 'react';
const SvgIconInfinity = ( props ) => (
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
			d="M41.465 38.875C44.968 41.098 48.595 43 51.09 43 57.091 43 62 38.05 62 32s-4.909-11-10.909-11c-12.182 0-26 22-38.182 22C6.91 43 2 38.05 2 32s4.91-11 10.91-11c4.188 0 8.055 3.025 11.633 5.132.065.039-.163-.095-.098-.058"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M14.375 29 25 26.153l-2.847-10.625"
		/>
	</svg>
);
export default SvgIconInfinity;
