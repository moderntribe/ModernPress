import * as React from 'react';
const SvgIconHeart = ( props ) => (
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
			d="M55.096 32 32 56 8.904 32A14.213 14.213 0 1 1 32 16a14.214 14.214 0 1 1 23.096 16Z"
		/>
	</svg>
);
export default SvgIconHeart;
