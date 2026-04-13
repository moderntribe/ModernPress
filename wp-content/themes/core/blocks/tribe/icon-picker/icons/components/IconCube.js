import * as React from 'react';
const SvgIconCube = ( props ) => (
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
			d="m8 18 24 14 24-14M32 32v28"
		/>
		<path
			stroke="currentColor"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M56 46V18L32 4 8 18v28l24 14z"
		/>
	</svg>
);
export default SvgIconCube;
