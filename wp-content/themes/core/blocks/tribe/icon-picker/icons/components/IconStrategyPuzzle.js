import * as React from 'react';
const SvgIconStrategyPuzzle = ( props ) => (
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
			d="M4 32h8v3a5 5 0 1 0 10 0v-3h20v-3a5 5 0 1 1 10 0v3h8"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M32 60v-8h3a5 5 0 1 0 0-10h-3V22h-3a5 5 0 1 1 0-10h3V4"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M60 4H4v56h56z"
		/>
	</svg>
);
export default SvgIconStrategyPuzzle;
