import * as React from 'react';
const SvgIconVideoReel = ( props ) => (
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
			d="M32 22a6 6 0 1 0 0-12 6 6 0 0 0 0 12ZM32 54a6 6 0 1 0 0-12 6 6 0 0 0 0 12ZM16 38a6 6 0 1 0 0-12 6 6 0 0 0 0 12ZM48 38a6 6 0 1 0 0-12 6 6 0 0 0 0 12Z"
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
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M32 62h30"
		/>
	</svg>
);
export default SvgIconVideoReel;
