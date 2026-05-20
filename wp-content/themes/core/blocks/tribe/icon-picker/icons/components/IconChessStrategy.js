import * as React from 'react';
const SvgIconChessStrategy = ( props ) => (
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
			d="M32 22c5.523 0 10-4.477 10-10S37.523 2 32 2 22 6.477 22 12s4.477 10 10 10ZM18 22h28"
		/>
		<path
			stroke="currentColor"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M38 22a47.73 47.73 0 0 0 10.6 30M15.4 52A47.73 47.73 0 0 0 26 22"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M54 52H10v10h44z"
		/>
	</svg>
);
export default SvgIconChessStrategy;
