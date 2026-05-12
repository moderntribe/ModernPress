import * as React from 'react';
const SvgIconTrophy = ( props ) => (
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
			d="M14 20h-2a8 8 0 0 1-8-8V2h10M50 20h2a8 8 0 0 0 8-8V2H50M32 52V40"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M32 40a18 18 0 0 1-18-18V2h36v20a18 18 0 0 1-18 18ZM46 62H18a10 10 0 0 1 10-10h8a10 10 0 0 1 10 10Z"
		/>
	</svg>
);
export default SvgIconTrophy;
