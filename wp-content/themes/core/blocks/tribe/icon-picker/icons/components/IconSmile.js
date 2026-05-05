import * as React from 'react';
const SvgIconSmile = ( props ) => (
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
			d="M32 62c16.569 0 30-13.431 30-30C62 15.432 48.569 2 32 2 15.432 2 2 15.432 2 32c0 16.569 13.432 30 30 30Z"
		/>
		<path
			fill="currentColor"
			d="M20 32a4 4 0 1 0 0-8 4 4 0 0 0 0 8M44 32a4 4 0 1 0 0-8 4 4 0 0 0 0 8"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M42.4 44a12 12 0 0 1-20.8 0"
		/>
	</svg>
);
export default SvgIconSmile;
