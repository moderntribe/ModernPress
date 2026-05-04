import * as React from 'react';
const SvgIconCircleCheck = ( props ) => (
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
			d="M54 24c1.2 3 2 6.4 2 10 0 14.4-11.6 26-26 26S4 48.4 4 34 15.6 8 30 8c5 0 9.8 1.4 13.8 4"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="m20 26 10 10L60 6"
		/>
	</svg>
);
export default SvgIconCircleCheck;
