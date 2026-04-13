import * as React from 'react';
const SvgIconLink = ( props ) => (
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
			d="M22 31.4a11.28 11.28 0 0 0-8.686 3.292l-6 6a11.314 11.314 0 0 0 16 16l6-6A11.28 11.28 0 0 0 32.6 42M31.4 22a11.28 11.28 0 0 1 3.292-8.686l6-6a11.314 11.314 0 0 1 16 16l-6 6A11.28 11.28 0 0 1 42 32.6M20 44l24-24"
		/>
	</svg>
);
export default SvgIconLink;
