import * as React from 'react';
const SvgIconCompassNavigation = ( props ) => (
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
			d="M32 2v8M62 32h-8M32 62v-8M2 32h8M26 26l12 12"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M32 62c16.569 0 30-13.431 30-30C62 15.432 48.569 2 32 2 15.432 2 2 15.432 2 32c0 16.569 13.432 30 30 30Z"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="m46 18-8 20-20 8 8-20z"
		/>
	</svg>
);
export default SvgIconCompassNavigation;
