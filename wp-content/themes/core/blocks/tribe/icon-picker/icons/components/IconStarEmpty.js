import * as React from 'react';
const SvgIconStarEmpty = ( props ) => (
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
			d="M32 5.372 40.652 22.9 60 25.712 46 39.358l3.304 19.27L32 49.534l-17.304 9.094L18 39.358 4 25.712 23.348 22.9z"
		/>
	</svg>
);
export default SvgIconStarEmpty;
