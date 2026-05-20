import * as React from 'react';
const SvgIconSuitcaseTravel = ( props ) => (
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
			d="M42 14V4H22v10"
		/>
		<path
			stroke="currentColor"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M14 14v44M50 14v44"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M56 14H8a6 6 0 0 0-6 6v32a6 6 0 0 0 6 6h48a6 6 0 0 0 6-6V20a6 6 0 0 0-6-6Z"
		/>
	</svg>
);
export default SvgIconSuitcaseTravel;
