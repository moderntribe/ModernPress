import * as React from 'react';
const SvgIconWorldGlobe = ( props ) => (
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
			d="M32 2v60M2 32h60"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M32 62c9.467 0 17.142-13.431 17.142-30 0-16.568-7.675-30-17.142-30S14.858 15.432 14.858 32c0 16.569 7.675 30 17.142 30Z"
		/>
		<path
			stroke="currentColor"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M6.62 16h50.76M6.62 48h50.76"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M32 62c16.569 0 30-13.431 30-30C62 15.432 48.569 2 32 2 15.432 2 2 15.432 2 32c0 16.569 13.432 30 30 30Z"
		/>
	</svg>
);
export default SvgIconWorldGlobe;
